<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopUp\ClaimPaymentRequest;
use App\Http\Requests\TopUp\PayRequest;
use App\Http\Requests\TopUp\StoreCheckoutRequest;
use App\Models\GameTitle;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\PromoService;
use App\Services\TransactionService;
use App\Support\ActivityLogger;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TopUpController extends Controller
{
    /** @var \App\Services\TransactionService */
    protected $transactions;

    /** @var \App\Services\Payment\PaymentGatewayManager */
    protected $gateways;

    /** @var \App\Services\PromoService */
    protected $promo;

    public function __construct(TransactionService $transactions, PaymentGatewayManager $gateways, PromoService $promo)
    {
        $this->transactions = $transactions;
        $this->gateways     = $gateways;
        $this->promo        = $promo;
    }

    /**
     * Daftar semua game yang aktif (search + filter kategori).
     */
    public function index(Request $request)
    {
        $search   = trim((string) $request->query('q', ''));
        $category = $request->query('kategori');

        $games = GameTitle::where('is_active', true)
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->orderBy('name')
            ->get();

        $categories = GameTitle::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Statistik banner = total katalog (tidak terpengaruh filter),
        // supaya angka di hero tetap merepresentasikan keseluruhan toko.
        $totalGames    = GameTitle::where('is_active', true)->count();
        $totalNominals = Product::where('is_active', true)->count();
        $paymentMethodCount = count(PaymentMethods::all());

        return view('topup.index', compact('games', 'categories', 'search', 'category', 'totalGames', 'totalNominals', 'paymentMethodCount'));
    }

    /**
     * Halaman detail satu game: form User ID + pilihan nominal.
     */
    public function show($slug)
    {
        $game = GameTitle::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $game->products()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('topup.show', [
            'game' => $game,
            'products' => $products,
        ]);
    }

    /**
     * STEP 1: simpan isian form ke session, lalu buka halaman checkout.
     */
    public function storeCheckout(StoreCheckoutRequest $request)
    {
        $data = $request->validated();

        $game = GameTitle::where('slug', $data['game_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $product = Product::where('id', $data['product_id'])
            ->where('game_title_id', $game->id)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            return back()->withErrors(['product_id' => 'Produk tidak ditemukan atau sudah nonaktif.']);
        }

        if ($product->stock < 1) {
            return back()->withErrors(['product_id' => 'Stok "' . $product->name . '" habis. Pilih nominal lain.']);
        }

        session()->put('topup_draft', [
            'game_id'      => $game->id,
            'game_name'    => $game->name,
            'game_slug'    => $game->slug,
            'game_icon'    => $game->icon,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'amount'       => (int) $product->price,
            'user_game_id' => $data['user_game_id'],
            // Nullable + field tidak dikirim -> key tidak ada di validated data.
            'server_id'    => $data['server_id'] ?? null,
        ]);

        return redirect()->route('topup.checkout');
    }

    /**
     * STEP 2: halaman checkout (ringkasan + pilih pembayaran).
     */
    public function checkout()
    {
        $draft = session('topup_draft');

        if (! $draft) {
            return redirect()->route('topup.index')
                ->with('error', 'Silakan pilih game dan nominal terlebih dahulu.');
        }

        // Kode promo dihitung ulang setiap halaman checkout dibuka: subtotal bisa
        // berubah (ganti nominal) dan promo bisa saja sudah kedaluwarsa.
        $promo = $this->refreshDraftPromo($draft);

        return view('topup.checkout', [
            'draft'          => $draft,
            'promoCode'      => $draft['promo_code'] ?? null,
            'discount'       => $promo,
            'paymentMethods' => PaymentMethods::labelled(),
        ]);
    }

    /**
     * Pasang / lepas kode promo pada draft checkout top up.
     */
    public function applyPromo(Request $request)
    {
        $draft = session('topup_draft');

        if (! $draft) {
            return redirect()->route('topup.index')
                ->with('error', 'Sesi checkout habis, silakan ulangi.');
        }

        if ($request->boolean('remove')) {
            unset($draft['promo_code'], $draft['promo_discount']);
            session()->put('topup_draft', $draft);

            return redirect()->route('topup.checkout')->with('success', 'Kode promo dihapus dari pesanan.');
        }

        $quote = $this->promo->quote($request->input('code'), (int) $draft['amount'], 'topup');

        if (! $quote['ok']) {
            return back()->withErrors(['promo_code' => $quote['message']]);
        }

        $draft['promo_code']     = $quote['promo']->code;
        $draft['promo_discount'] = $quote['discount'];
        session()->put('topup_draft', $draft);

        return redirect()->route('topup.checkout')->with(
            'success',
            'Kode promo "' . $quote['promo']->code . '" dipakai. Kamu hemat Rp ' . number_format($quote['discount'], 0, ',', '.') . '.'
        );
    }

    /**
     * Hitung ulang potongan promo draft; buang kodenya bila sudah tidak berlaku.
     *
     * @param  array<string, mixed>  $draft
     * @return int  Potongan rupiah yang berlaku.
     */
    private function refreshDraftPromo(array &$draft)
    {
        if (empty($draft['promo_code'])) {
            return 0;
        }

        $quote = $this->promo->quote($draft['promo_code'], (int) $draft['amount'], 'topup');

        if (! $quote['ok']) {
            unset($draft['promo_code'], $draft['promo_discount']);
            session()->put('topup_draft', $draft);

            return 0;
        }

        $draft['promo_code']     = $quote['promo']->code;
        $draft['promo_discount'] = $quote['discount'];
        session()->put('topup_draft', $draft);

        return $quote['discount'];
    }

    /**
     * STEP 3: buat transaksi berstatus pending + tahan (kurangi) stok.
     */
    public function pay(PayRequest $request)
    {
        $draft = session('topup_draft');

        if (! $draft) {
            return redirect()->route('topup.index')
                ->with('error', 'Sesi checkout habis, silakan ulangi.');
        }

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Kunci baris product agar dua pembeli tidak berebut stok terakhir.
            $product = Product::whereKey($draft['product_id'])->lockForUpdate()->first();

            if (! $product || ! $product->is_active) {
                DB::rollBack();

                return redirect()->route('topup.index')
                    ->with('error', 'Produk "' . $draft['product_name'] . '" sudah tidak tersedia.');
            }

            if ($product->stock < 1) {
                DB::rollBack();

                return redirect()->route('topup.show', $draft['game_slug'])
                    ->withErrors(['product_id' => 'Stok "' . $product->name . '" habis. Pilih nominal lain.']);
            }

            // Kode promo divalidasi ulang DI DALAM transaksi dengan baris promo
            // dikunci, supaya kuota pemakaian tidak bisa dilewati dua pembeli
            // yang menekan "Bayar" bersamaan.
            $discount = 0;
            $promoCode = null;

            if (! empty($draft['promo_code'])) {
                $quote = $this->promo->quote($draft['promo_code'], (int) $draft['amount'], 'topup', true);

                if (! $quote['ok']) {
                    DB::rollBack();

                    return redirect()->route('topup.checkout')
                        ->withErrors(['promo_code' => $quote['message']]);
                }

                $discount  = $quote['discount'];
                $promoCode = $quote['promo']->code;

                $quote['promo']->increment('used_count');
            }

            $transaction = Transaction::create([
                'transaction_code' => $this->generateCode('TXN'),
                'user_id'          => auth()->id(),
                'game_title_id'    => $draft['game_id'],
                'product_id'       => $draft['product_id'],
                'user_game_id'     => $draft['user_game_id'],
                'server_id'        => $draft['server_id'],
                'payment_method'   => $validated['payment_method'],
                'promo_code'       => $promoCode,
                'discount_amount'  => $discount,
                'amount'           => (int) $draft['amount'] - $discount,
                'status'           => 'pending',
            ]);

            // Buat tagihan ke gateway (simulasi/Midtrans). Gagal gateway tidak
            // boleh membatalkan transaksi yang sudah dibuat.
            try {
                $charge = $this->gateways->driver()->createCharge(
                    $transaction->transaction_code,
                    (int) $transaction->amount,
                    (string) $transaction->payment_method,
                    [
                        'customer'   => $this->customerDetails($transaction),
                        'finish_url' => route('topup.success', $transaction->transaction_code),
                    ]
                );

                $transaction->update([
                    'payment_provider'  => $this->gateways->driver()->name(),
                    'payment_reference' => $charge->providerReference,
                    'payment_url'       => $charge->redirectUrl,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            $product->decrement('stock');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        session()->forget('topup_draft');

        return redirect()->route('topup.success', $transaction->transaction_code);
    }

    /**
     * Data pelanggan untuk gateway (bila tersedia).
     *
     * @return array<string, mixed>
     */
    private function customerDetails(Transaction $transaction)
    {
        $user = $transaction->user;

        return array_filter([
            'first_name' => $user->name ?? 'Guest',
            'email'      => $user->email ?? null,
        ]);
    }

    /**
     * Tandai pembayaran sudah dilakukan (simulasi): pembeli menekan
     * "Saya Sudah Bayar". Status tetap pending sampai admin memverifikasi.
     *
     * Pembeli boleh mengunggah bukti pembayaran (opsional).
     */
    public function claimPayment(ClaimPaymentRequest $request, $code)
    {
        $transaction = $this->findOwnedTransaction($code);

        $proofPath = null;

        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')
                ->store('payment-proofs', 'public');
        }

        $claimed = $this->transactions->claimPayment($transaction, $proofPath);

        if ($claimed) {
            ActivityLogger::log(
                'transaction.claim',
                'Pembeli menandai transaksi ' . $transaction->transaction_code . ' sudah dibayar'
                    . ($proofPath ? ' (dengan bukti pembayaran).' : '.'),
                $transaction
            );
        } elseif ($proofPath) {
            // Klaim tidak tercatat (mis. pesanan sudah dikonfirmasi atau sudah
            // punya bukti): jangan tinggalkan berkas unggahan tanpa rujukan.
            Storage::disk('public')->delete($proofPath);
        }

        // Verifikasi otomatis dari bukti (kebijakan hybrid/auto) memberi pesan
        // berbeda supaya pembeli tahu pesanannya langsung selesai.
        $autoConfirmed = $transaction->fresh()->isSuccess()
            && $transaction->fresh()->confirmedByAuto();

        if ($autoConfirmed) {
            ActivityLogger::log(
                'transaction.auto-confirm',
                'Transaksi ' . $transaction->transaction_code . ' terverifikasi otomatis dari bukti pembayaran.',
                $transaction
            );

            return redirect()->route('topup.success', $code)
                ->with('success', 'Pembayaran terverifikasi! Pesananmu langsung diproses dan selesai.');
        }

        return redirect()->route('topup.success', $code)
            ->with('success', 'Terima kasih! Pembayaranmu sudah kami terima dan sedang menunggu verifikasi admin.');
    }

    /**
     * Batalkan pesanan pending, stok dikembalikan.
     */
    public function cancelPayment($code)
    {
        $transaction = $this->findOwnedTransaction($code);

        if ($transaction->status === 'pending') {
            $this->transactions->transitionTo($transaction, 'cancelled');
        }

        return redirect()->route('topup.success', $code)
            ->with('success', 'Pesanan dibatalkan. Stok kembali tersedia.');
    }

    /**
     * Halaman sukses/pembayaran + detail transaksi.
     */
    public function success($code)
    {
        $transaction = $this->findOwnedTransaction($code);

        return view('topup.success', compact('transaction'));
    }

    /**
     * Ambil transaksi yang boleh diakses user (dijaga oleh TransactionPolicy).
     */
    private function findOwnedTransaction($code)
    {
        $transaction = Transaction::where('transaction_code', $code)
            ->with(['gameTitle', 'product', 'confirmedBy', 'revokedBy'])
            ->firstOrFail();

        $this->authorize('view', $transaction);

        return $transaction;
    }

    /**
     * Buat kode transaksi unik, contoh: TXN-20261009-A1B2C3
     */
    private function generateCode($prefix)
    {
        do {
            $code = $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Transaction::where('transaction_code', $code)->exists());

        return $code;
    }
}
