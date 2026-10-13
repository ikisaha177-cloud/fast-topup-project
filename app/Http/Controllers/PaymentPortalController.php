<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Transaction;
use App\Services\MarketplaceOrderService;
use App\Services\TransactionService;
use App\Support\ActivityLogger;
use App\Support\OrderStatus;
use App\Support\PaymentChannel;
use Illuminate\Http\Request;

/**
 * Portal pembayaran — halaman "web lain" tempat pembeli diarahkan setelah
 * checkout untuk memilih/menjalankan pembayaran (QRIS, e-wallet, transfer
 * bank), lalu menerima STRUK yang bisa di-screenshot dan diunggah sebagai
 * bukti pembayaran di halaman pesanan.
 *
 * Halaman ini memakai layout sendiri ({@see resources/views/layouts/gateway})
 * supaya terasa sebagai situs pembayaran terpisah, dan sengaja TIDAK
 * memutuskan status pesanan sendiri: menekan "Konfirmasi Pembayaran" hanya
 * menandai "sudah bayar" lewat service yang sama dengan tombol di toko,
 * sehingga aturan verifikasi (manual/hybrid/auto) tetap berlaku persis.
 */
class PaymentPortalController extends Controller
{
    /** @var \App\Services\TransactionService */
    protected $transactions;

    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    /**
     * Keadaan portal: 'payable' (belum bayar), 'receipt' (struk tampil),
     * atau 'closed' (gagal/batal, tidak ada yang bisa dibayar).
     *
     * Gagal/batal selalu didahulukan — pesanan yang pernah ditandai sudah bayar
     * tapi kemudian ditolak admin tidak boleh tetap menampilkan struk.
     */
    private const STATE_CLOSED = 'closed';
    private const STATE_PAYABLE = 'payable';
    private const STATE_RECEIPT = 'receipt';

    public function __construct(TransactionService $transactions, MarketplaceOrderService $orders)
    {
        $this->transactions = $transactions;
        $this->orders       = $orders;
    }

    /**
     * Tampilkan halaman pembayaran (instruksi bayar) atau struk.
     */
    public function show($code)
    {
        [$model, $type] = $this->resolve($code);

        $this->authorize('view', $model);

        return view('payment.show', [
            'doc'     => $this->document($model, $type),
            'channel' => PaymentChannel::forMethod(
                (string) $model->payment_method,
                $code,
                $this->amountOf($model, $type)
            ),
            'backUrl' => $this->backUrl($model, $type),
        ]);
    }

    /**
     * Pembeli menekan "Konfirmasi Pembayaran" di portal.
     *
     * Tidak mengonfirmasi pesanan sendiri — hanya menandai sudah bayar (dan
     * membiarkan kebijakan verifikasi yang menentukan), lalu menampilkan struk.
     */
    public function confirm(Request $request, $code)
    {
        [$model, $type] = $this->resolve($code);

        $this->authorize('view', $model);

        $service = $type === 'marketplace' ? $this->orders : $this->transactions;
        $recorded = $service->claimPayment($model, null);

        if ($recorded) {
            ActivityLogger::log(
                ($type === 'marketplace' ? 'order' : 'transaction') . '.portal-confirm',
                'Pembeli mengonfirmasi pembayaran lewat portal untuk '
                    . $this->codeOf($model, $type) . '.',
                $model
            );
        }

        return redirect()
            ->route('payment.show', $this->codeOf($model, $type))
            ->with('success', $recorded
                ? 'Pembayaran dikonfirmasi. Simpan struk di bawah sebagai bukti.'
                : 'Status pesanan ini sudah tidak menunggu pembayaran.');
    }

    /**
     * Ambil model (transaksi top up atau pesanan marketplace) dari kode.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Model, 1: string}
     */
    private function resolve($code)
    {
        $transaction = Transaction::where('transaction_code', $code)
            ->with(['gameTitle', 'product', 'user'])
            ->first();

        if ($transaction) {
            return [$transaction, 'topup'];
        }

        $order = MarketplaceOrder::where('order_code', $code)
            ->with(['items.product', 'buyer'])
            ->firstOrFail();

        // Policy pesanan selalu butuh akun pembeli (type-hint User non-nullable);
        // tanpa guard ini guest akan memicu TypeError, bukan 403.
        abort_unless(auth()->user(), 403, 'Pesanan marketplace hanya dapat dibuka oleh pemiliknya.');

        return [$order, 'marketplace'];
    }

    private function amountOf($model, string $type): int
    {
        return (int) ($type === 'marketplace' ? $model->total_amount : $model->amount);
    }

    private function codeOf($model, string $type): string
    {
        return (string) ($type === 'marketplace' ? $model->order_code : $model->transaction_code);
    }

    private function backUrl($model, string $type): string
    {
        return $type === 'marketplace'
            ? route('marketplace.success', $model->order_code)
            : route('topup.success', $model->transaction_code);
    }

    /**
     * Susun data tampilan (instruksi bayar & struk) dari model.
     *
     * @return array<string, mixed>
     */
    private function document($model, string $type): array
    {
        return [
            'code'         => $this->codeOf($model, $type),
            'kind'         => $type === 'marketplace' ? 'Pesanan Marketplace' : 'Top Up Game',
            'amount'       => $this->amountOf($model, $type),
            'method'       => (string) $model->payment_method,
            'status'       => $model->status,
            'label'        => $model->statusLabel(),
            'state'        => $this->state($model),
            'paid'         => $model->isSuccess(),
            'reference'    => $model->payment_reference,
            'buyer'        => ['name' => $this->buyerName($model, $type)],
            'created_at'   => $model->created_at,
            'confirmed_at' => $model->confirmed_at,
            'promo_code'   => $model->promo_code,
            'discount'     => (int) $model->discount_amount,
            'items'        => $this->items($model, $type),
        ];
    }

    /** Nama pembeli, "Guest" bila top up dilakukan tanpa akun. */
    private function buyerName($model, string $type): string
    {
        $user = $type === 'marketplace' ? $model->buyer : $model->user;

        return ($user->name ?? null) ?: 'Guest';
    }

    /**
     * Tentukan keadaan halaman portal.
     *
     * @return string self::STATE_* — salah satunya selalu.
     */
    private function state($model): string
    {
        if (in_array($model->status, [OrderStatus::FAILED, OrderStatus::CANCELLED], true)) {
            return self::STATE_CLOSED;
        }

        if ($model->isPaymentClaimed() || $model->isSuccess()) {
            return self::STATE_RECEIPT;
        }

        if ($model->isPending()) {
            return self::STATE_PAYABLE;
        }

        return self::STATE_CLOSED;
    }

    /**
     * Daftar item untuk baris struk.
     *
     * @return array<int, array<string, mixed>>
     */
    private function items($model, string $type): array
    {
        if ($type === 'topup') {
            return [[
                'name' => ($model->gameTitle->name ?? 'Top Up'),
                'sub'  => ($model->product->name ?? '-')
                    . ($model->user_game_id ? ' · ID ' . $model->user_game_id : ''),
                'qty'  => 1,
                // Harga satuan = harga katalog; potongan promo ditampilkan terpisah.
                'price' => (int) $model->amount + (int) $model->discount_amount,
            ]];
        }

        $items = [];

        foreach ($model->items as $item) {
            $items[] = [
                'name'  => $item->product->name ?? 'Item',
                'sub'   => $item->product->category ?? 'Marketplace',
                'qty'   => (int) $item->quantity,
                'price' => (int) $item->subtotal,
            ];
        }

        return $items;
    }
}
