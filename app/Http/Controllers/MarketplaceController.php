<?php

namespace App\Http\Controllers;

use App\Http\Requests\Marketplace\AddToCartRequest;
use App\Http\Requests\Marketplace\ClaimPaymentRequest;
use App\Http\Requests\Marketplace\ProcessCheckoutRequest;
use App\Http\Requests\Marketplace\UpdateCartRequest;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Review;
use App\Services\MarketplaceOrderService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\PromoService;
use App\Support\ActivityLogger;
use App\Support\OrderStatus;
use App\Support\PaymentMethods;
use App\Support\PaymentWindow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    /** @var \App\Services\Payment\PaymentGatewayManager */
    protected $gateways;

    /** @var \App\Services\PromoService */
    protected $promo;

    public function __construct(MarketplaceOrderService $orders, PaymentGatewayManager $gateways, PromoService $promo)
    {
        $this->orders   = $orders;
        $this->gateways = $gateways;
        $this->promo    = $promo;
    }

    /**
     * Daftar produk marketplace (search + filter kategori + pagination).
     */
    public function index(Request $request)
    {
        $search   = trim((string) $request->query('q', ''));
        $category = $request->query('kategori');

        $products = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->with('seller')
            ->withSoldCount()
            ->withRating()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category, function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = MarketplaceProduct::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // Statistik banner = total katalog aktif (tidak terpengaruh filter).
        $totalProducts = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->count();

        $sellerCount = MarketplaceProduct::where('is_active', true)
            ->distinct()
            ->count('user_id');

        return view('marketplace.index', compact('products', 'categories', 'search', 'category', 'totalProducts', 'sellerCount'));
    }

    /**
     * Detail satu produk marketplace + rekomendasi serupa.
     */
    public function show($slug)
    {
        $product = MarketplaceProduct::where('slug', $slug)
            ->where('is_active', true)
            ->with('seller')
            ->withSoldCount()
            ->withRating()
            ->firstOrFail();

        $related = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->whereKeyNot($product->id)
            ->when($product->category, function ($query) use ($product) {
                $query->where('category', $product->category);
            })
            ->latest()
            ->withSoldCount()
            ->withRating()
            ->take(4)
            ->get();

        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        // Pesanan Selesai milik pengunjung untuk produk ini — satu-satunya
        // dasar yang sah untuk menampilkan formulir ulasan.
        $myOrder = null;
        $myReview = null;

        if (auth()->check() && ! auth()->user()->isAdmin()) {
            $myOrder = MarketplaceOrder::where('user_id', auth()->id())
                ->where('status', OrderStatus::SUCCESS)
                ->whereHas('items', function ($query) use ($product) {
                    $query->where('marketplace_product_id', $product->id);
                })
                ->latest()
                ->first();

            $myReview = Review::where('user_id', auth()->id())
                ->where('marketplace_product_id', $product->id)
                ->first();
        }

        return view('marketplace.show', compact('product', 'related', 'reviews', 'myOrder', 'myReview'));
    }

    /**
     * Tambah produk ke keranjang (session).
     */
    public function addToCart(AddToCartRequest $request, $slug)
    {
        $product = MarketplaceProduct::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $quantity = (int) $request->validated()['quantity'];

        if ($quantity > $product->stock) {
            return back()->withErrors([
                'quantity' => 'Stok tidak mencukupi. Sisa stok: ' . $product->stock,
            ]);
        }

        $cart = session()->get('cart', []);

        $newQty = $quantity + (isset($cart[$product->id]) ? $cart[$product->id]['quantity'] : 0);

        if ($newQty > $product->stock) {
            return back()->withErrors([
                'quantity' => 'Jumlah di keranjang melebihi stok. Sisa stok: ' . $product->stock,
            ]);
        }

        $cart[$product->id] = [
            'id'       => $product->id,
            'name'     => $product->name,
            'slug'     => $product->slug,
            'price'    => (int) $product->price,
            'quantity' => $newQty,
            'stock'    => (int) $product->stock,
            'image'    => $product->image,
        ];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Produk masuk ke keranjang.');
    }

    /**
     * Halaman keranjang.
     */
    public function cart()
    {
        $cart = $this->getCart();

        return view('marketplace.cart', [
            'cart'  => $cart,
            'total' => $this->total($cart),
        ]);
    }

    /**
     * Ubah quantity produk di keranjang.
     */
    public function updateCart(UpdateCartRequest $request)
    {
        $data = $request->validated();

        $cart = $this->getCart();
        $id   = (int) $data['product_id'];

        if (! isset($cart[$id])) {
            return redirect()->route('cart.index')->with('error', 'Produk tidak ada di keranjang.');
        }

        $quantity = (int) $data['quantity'];

        if ($quantity > $cart[$id]['stock']) {
            return back()->withErrors([
                'quantity_' . $id => 'Stok tidak mencukupi. Sisa stok: ' . $cart[$id]['stock'],
            ]);
        }

        $cart[$id]['quantity'] = $quantity;
        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Keranjang diperbarui.');
    }

    /**
     * Hapus produk dari keranjang.
     */
    public function removeCart(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer']);

        $cart = $this->getCart();
        unset($cart[(int) $data['product_id']]);

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    /**
     * Halaman checkout marketplace.
     */
    public function checkout()
    {
        $cart = $this->getCart();

        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Keranjang masih kosong.');
        }

        $subtotal = $this->total($cart);
        $promo    = $this->refreshCartPromo($subtotal);

        return view('marketplace.checkout', [
            'cart'           => $cart,
            'subtotal'       => $subtotal,
            'promoCode'      => $promo['code'],
            'discount'       => $promo['discount'],
            'total'          => $subtotal - $promo['discount'],
            'paymentMethods' => PaymentMethods::labelled(),
        ]);
    }

    /**
     * Pasang / lepas kode promo pada keranjang yang sedang di-checkout.
     */
    public function applyPromo(Request $request)
    {
        $cart = $this->getCart();

        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Keranjang masih kosong.');
        }

        if ($request->boolean('remove')) {
            session()->forget('promo_code');

            return redirect()->route('marketplace.checkout')->with('success', 'Kode promo dihapus dari pesanan.');
        }

        $quote = $this->promo->quote($request->input('code'), $this->total($cart), 'marketplace');

        if (! $quote['ok']) {
            return back()->withErrors(['promo_code' => $quote['message']]);
        }

        session()->put('promo_code', $quote['promo']->code);

        return redirect()->route('marketplace.checkout')->with(
            'success',
            'Kode promo "' . $quote['promo']->code . '" dipakai. Kamu hemat Rp ' . number_format($quote['discount'], 0, ',', '.') . '.'
        );
    }

    /**
     * Hitung ulang potongan promo keranjang; lupakan kodenya bila sudah tidak berlaku.
     *
     * @return array{code: string|null, discount: int}
     */
    private function refreshCartPromo($subtotal)
    {
        $code = session('promo_code');

        if (! $code) {
            return ['code' => null, 'discount' => 0];
        }

        $quote = $this->promo->quote($code, $subtotal, 'marketplace');

        if (! $quote['ok']) {
            session()->forget('promo_code');

            return ['code' => null, 'discount' => 0];
        }

        return ['code' => $quote['promo']->code, 'discount' => $quote['discount']];
    }

    /**
     * Proses checkout: buat order, kurangi stok, kosongkan keranjang.
     */
    public function processCheckout(ProcessCheckoutRequest $request)
    {
        $cart = $this->getCart();

        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Keranjang masih kosong.');
        }

        $validated = $request->validated();
        $subtotal  = $this->total($cart);

        DB::beginTransaction();

        try {
            // Kode promo divalidasi DI DALAM transaksi dengan baris promo dikunci,
            // supaya kuota pemakaian tidak bisa dilewati dua pembeli bersamaan.
            $discount  = 0;
            $promoCode = null;

            if (session('promo_code')) {
                $quote = $this->promo->quote(session('promo_code'), $subtotal, 'marketplace', true);

                if (! $quote['ok']) {
                    DB::rollBack();

                    return back()->withErrors(['promo_code' => $quote['message']]);
                }

                $discount  = $quote['discount'];
                $promoCode = $quote['promo']->code;

                $quote['promo']->increment('used_count');
            }

            // Kunci semua produk, cek ulang stok di server (jangan percaya input browser).
            $locked = [];

            foreach ($cart as $item) {
                $product = MarketplaceProduct::whereKey($item['id'])->lockForUpdate()->first();

                if (! $product || ! $product->is_active) {
                    DB::rollBack();

                    return back()->withErrors([
                        'cart' => 'Produk "' . $item['name'] . '" sudah tidak tersedia.',
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();

                    return back()->withErrors([
                        'cart' => 'Stok "' . $product->name . '" tidak mencukupi.',
                    ]);
                }

                $locked[$product->id] = $product;
            }

            $order = MarketplaceOrder::create([
                'order_code'      => $this->generateOrderCode(),
                'user_id'         => auth()->id(),
                'total_amount'    => $subtotal - $discount,
                'promo_code'      => $promoCode,
                'discount_amount' => $discount,
                'payment_method'  => $validated['payment_method'],
                'status'          => 'pending',
                'expires_at'      => PaymentWindow::deadline(),
            ]);

            // Buat tagihan ke gateway. Kegagalan gateway tidak membatalkan order.
            try {
                $charge = $this->gateways->driver()->createCharge(
                    $order->order_code,
                    (int) $order->total_amount,
                    (string) $order->payment_method,
                    [
                        'customer'   => array_filter([
                            'first_name' => $order->buyer->name ?? 'Guest',
                            'email'      => $order->buyer->email ?? null,
                        ]),
                        'finish_url' => route('marketplace.success', $order->order_code),
                    ]
                );

                $order->update([
                    'payment_provider'  => $this->gateways->driver()->name(),
                    'payment_reference' => $charge->providerReference,
                    'payment_url'       => $charge->redirectUrl,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            foreach ($cart as $item) {
                $product  = $locked[$item['id']];
                $subtotal = $item['price'] * $item['quantity'];

                $order->items()->create([
                    'marketplace_product_id' => $product->id,
                    'quantity'               => $item['quantity'],
                    'price'                  => $item['price'],
                    'subtotal'               => $subtotal,
                ]);

                // Tahan stok selama pesanan masih pending.
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        session()->forget('cart');
        session()->forget('promo_code');

        return redirect()->route('marketplace.success', $order->order_code);
    }

    /**
     * Tandai pembayaran sudah dilakukan: pembeli menekan "Saya Sudah Bayar".
     * Status tetap pending sampai admin memverifikasi.
     *
     * Pembeli boleh mengunggah bukti pembayaran (opsional).
     */
    public function claimPayment(ClaimPaymentRequest $request, $code)
    {
        $order = $this->findOwnedOrder($code);

        // Batas waktu lewat? Batalkan dulu (lazy expiry) supaya pembeli tidak
        // bisa menandai "sudah bayar" pada pesanan yang sudah kedaluwarsa.
        $this->orders->expireIfDue($order);
        $order->refresh();

        if (! $order->isPending()) {
            return redirect()->route('marketplace.success', $code)
                ->with('error', $order->closedPaymentMessage());
        }

        $proofPath = null;

        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')
                ->store('payment-proofs', 'public');
        }

        $claimed = $this->orders->claimPayment($order, $proofPath);

        if ($claimed) {
            ActivityLogger::log(
                'order.claim',
                'Pembeli menandai pesanan ' . $order->order_code . ' sudah dibayar'
                    . ($proofPath ? ' (dengan bukti pembayaran).' : '.'),
                $order
            );
        } elseif ($proofPath) {
            // Klaim tidak tercatat (mis. pesanan sudah dikonfirmasi atau sudah
            // punya bukti): jangan tinggalkan berkas unggahan tanpa rujukan.
            Storage::disk('public')->delete($proofPath);
        }

        // Verifikasi otomatis dari bukti (kebijakan hybrid/auto) memberi pesan
        // berbeda supaya pembeli tahu pesanannya langsung selesai.
        $autoConfirmed = $order->fresh()->isSuccess()
            && $order->fresh()->confirmedByAuto();

        if ($autoConfirmed) {
            ActivityLogger::log(
                'order.auto-confirm',
                'Pesanan ' . $order->order_code . ' terverifikasi otomatis dari bukti pembayaran.',
                $order
            );

            return redirect()->route('marketplace.success', $code)
                ->with('success', 'Pembayaran terverifikasi! Pesananmu langsung diproses dan selesai.');
        }

        return redirect()->route('marketplace.success', $code)
            ->with('success', 'Terima kasih! Pembayaranmu sudah kami terima dan sedang menunggu verifikasi admin.');
    }

    /**
     * Batalkan pesanan pending, stok dikembalikan ke penjual.
     */
    public function cancelPayment($code)
    {
        $order = $this->findOwnedOrder($code);

        // Batas waktu lewat? Biarkan sistem yang membatalkan agar jejaknya
        // mencatat alasan "kedaluwarsa", bukan seolah pembeli yang membatalkan.
        if ($this->orders->expireIfDue($order)) {
            return redirect()->route('marketplace.success', $code)
                ->with('error', $order->refresh()->closedPaymentMessage());
        }

        if ($order->isPending()) {
            $this->orders->transitionTo($order, 'cancelled');

            return redirect()->route('marketplace.success', $code)
                ->with('success', 'Pesanan dibatalkan. Stok dikembalikan ke penjual.');
        }

        return redirect()->route('marketplace.success', $code)
            ->with('error', $order->refresh()->closedPaymentMessage());
    }

    /**
     * Halaman sukses/pembayaran order marketplace.
     */
    public function success($code)
    {
        $order = $this->findOwnedOrder($code);

        // Lazy expiry: pesanan pending yang sudah lewat batas waktu dibatalkan
        // tepat ketika halamannya dibuka — tanpa cron.
        $this->orders->expireIfDue($order);

        return view('marketplace.success', compact('order'));
    }

    /**
     * Ambil order yang boleh diakses user (dijaga oleh MarketplaceOrderPolicy).
     */
    private function findOwnedOrder($code)
    {
        $order = MarketplaceOrder::where('order_code', $code)
            ->with(['items.product', 'buyer', 'confirmedBy', 'revokedBy'])
            ->firstOrFail();

        $this->authorize('view', $order);

        return $order;
    }

    /**
     * Ambil cart dari session.
     */
    private function getCart()
    {
        return session()->get('cart', []);
    }

    /**
     * Hitung total belanja.
     */
    private function total($cart)
    {
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    /**
     * Buat kode order unik, contoh: MKT-20261009-A1B2C3
     */
    private function generateOrderCode()
    {
        do {
            $code = 'MKT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (MarketplaceOrder::where('order_code', $code)->exists());

        return $code;
    }
}
