<?php

namespace App\Http\Controllers;

use App\Http\Requests\TopUp\PayRequest;
use App\Http\Requests\TopUp\StoreCheckoutRequest;
use App\Models\GameTitle;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\TransactionService;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TopUpController extends Controller
{
    /** @var \App\Services\TransactionService */
    protected $transactions;

    /** @var \App\Services\Payment\PaymentGatewayManager */
    protected $gateways;

    public function __construct(TransactionService $transactions, PaymentGatewayManager $gateways)
    {
        $this->transactions = $transactions;
        $this->gateways     = $gateways;
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

        return view('topup.index', compact('games', 'categories', 'search', 'category', 'totalGames', 'totalNominals'));
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
            return back()->withErrors(['product_id' => 'Stock "' . $product->name . '" habis. Pilih nominal lain.']);
        }

        session()->put('topup_draft', [
            'game_id'      => $game->id,
            'game_name'    => $game->name,
            'game_slug'    => $game->slug,
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

        return view('topup.checkout', [
            'draft'          => $draft,
            'paymentMethods' => PaymentMethods::labelled(),
        ]);
    }

    /**
     * STEP 3: buat transaksi berstatus pending + tahan (kurangi) stock.
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
            // Kunci baris product agar dua pembeli tidak berebut stock terakhir.
            $product = Product::whereKey($draft['product_id'])->lockForUpdate()->first();

            if (! $product || ! $product->is_active) {
                DB::rollBack();

                return redirect()->route('topup.index')
                    ->with('error', 'Produk "' . $draft['product_name'] . '" sudah tidak tersedia.');
            }

            if ($product->stock < 1) {
                DB::rollBack();

                return redirect()->route('topup.show', $draft['game_slug'])
                    ->withErrors(['product_id' => 'Stock "' . $product->name . '" habis. Pilih nominal lain.']);
            }

            $transaction = Transaction::create([
                'transaction_code' => $this->generateCode('TXN'),
                'user_id'          => auth()->id(),
                'game_title_id'    => $draft['game_id'],
                'product_id'       => $draft['product_id'],
                'user_game_id'     => $draft['user_game_id'],
                'server_id'        => $draft['server_id'],
                'payment_method'   => $validated['payment_method'],
                'amount'           => $draft['amount'],
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
     * Konfirmasi pembayaran (simulasi gateway): pending -> success.
     */
    public function confirmPayment($code)
    {
        $transaction = $this->findOwnedTransaction($code);

        if ($transaction->status === 'pending') {
            $this->transactions->transitionTo($transaction, 'success');
        }

        return redirect()->route('topup.success', $code)
            ->with('success', 'Pembayaran diterima. Pesananmu diproses.');
    }

    /**
     * Batalkan pesanan pending, stock dikembalikan.
     */
    public function cancelPayment($code)
    {
        $transaction = $this->findOwnedTransaction($code);

        if ($transaction->status === 'pending') {
            $this->transactions->transitionTo($transaction, 'cancelled');
        }

        return redirect()->route('topup.success', $code)
            ->with('success', 'Pesanan dibatalkan. Stock kembali tersedia.');
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
        $transaction = Transaction::where('transaction_code', $code)->firstOrFail();

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
