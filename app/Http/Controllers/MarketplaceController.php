<?php

namespace App\Http\Controllers;

use App\Http\Requests\Marketplace\AddToCartRequest;
use App\Http\Requests\Marketplace\ProcessCheckoutRequest;
use App\Http\Requests\Marketplace\UpdateCartRequest;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Services\MarketplaceOrderService;
use App\Services\Payment\PaymentGatewayManager;
use App\Support\ActivityLogger;
use App\Support\PaymentMethods;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    /** @var \App\Services\Payment\PaymentGatewayManager */
    protected $gateways;

    public function __construct(MarketplaceOrderService $orders, PaymentGatewayManager $gateways)
    {
        $this->orders   = $orders;
        $this->gateways = $gateways;
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

        return view('marketplace.index', compact('products', 'categories', 'search', 'category', 'totalProducts'));
    }

    /**
     * Detail satu produk marketplace + rekomendasi serupa.
     */
    public function show($slug)
    {
        $product = MarketplaceProduct::where('slug', $slug)
            ->where('is_active', true)
            ->with('seller')
            ->firstOrFail();

        $related = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->whereKeyNot($product->id)
            ->when($product->category, function ($query) use ($product) {
                $query->where('category', $product->category);
            })
            ->latest()
            ->take(4)
            ->get();

        return view('marketplace.show', compact('product', 'related'));
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
                'quantity' => 'Stock tidak mencukupi. Sisa stock: ' . $product->stock,
            ]);
        }

        $cart = session()->get('cart', []);

        $newQty = $quantity + (isset($cart[$product->id]) ? $cart[$product->id]['quantity'] : 0);

        if ($newQty > $product->stock) {
            return back()->withErrors([
                'quantity' => 'Jumlah di keranjang melebihi stock. Sisa stock: ' . $product->stock,
            ]);
        }

        $cart[$product->id] = [
            'id'       => $product->id,
            'name'     => $product->name,
            'slug'     => $product->slug,
            'price'    => (int) $product->price,
            'quantity' => $newQty,
            'stock'    => (int) $product->stock,
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
                'quantity_' . $id => 'Stock tidak mencukupi. Sisa stock: ' . $cart[$id]['stock'],
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

        return view('marketplace.checkout', [
            'cart'           => $cart,
            'total'          => $this->total($cart),
            'paymentMethods' => PaymentMethods::labelled(),
        ]);
    }

    /**
     * Proses checkout: buat order, kurangi stock, kosongkan keranjang.
     */
    public function processCheckout(ProcessCheckoutRequest $request)
    {
        $cart = $this->getCart();

        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Keranjang masih kosong.');
        }

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Kunci semua produk, cek ulang stock di server (jangan percaya input browser).
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
                        'cart' => 'Stock "' . $product->name . '" tidak mencukupi.',
                    ]);
                }

                $locked[$product->id] = $product;
            }

            $order = MarketplaceOrder::create([
                'order_code'     => $this->generateOrderCode(),
                'user_id'        => auth()->id(),
                'total_amount'   => $this->total($cart),
                'payment_method' => $validated['payment_method'],
                'status'         => 'pending',
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

                // Tahan stock selama pesanan masih pending.
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        session()->forget('cart');

        return redirect()->route('marketplace.success', $order->order_code);
    }

    /**
     * Tandai pembayaran sudah dilakukan: pembeli menekan "Saya Sudah Bayar".
     * Status tetap pending sampai admin memverifikasi.
     */
    public function claimPayment($code)
    {
        $order = $this->findOwnedOrder($code);

        $claimed = $this->orders->claimPayment($order);

        if ($claimed) {
            ActivityLogger::log(
                'order.claim',
                'Pembeli menandai pesanan ' . $order->order_code . ' sudah dibayar.',
                $order
            );
        }

        return redirect()->route('marketplace.success', $code)
            ->with('success', 'Terima kasih! Pembayaranmu sudah kami terima dan sedang menunggu verifikasi admin.');
    }

    /**
     * Batalkan pesanan pending, stock dikembalikan ke penjual.
     */
    public function cancelPayment($code)
    {
        $order = $this->findOwnedOrder($code);

        if ($order->status === 'pending') {
            $this->orders->transitionTo($order, 'cancelled');
        }

        return redirect()->route('marketplace.success', $code)
            ->with('success', 'Pesanan dibatalkan. Stock dikembalikan ke penjual.');
    }

    /**
     * Halaman sukses/pembayaran order marketplace.
     */
    public function success($code)
    {
        $order = $this->findOwnedOrder($code);

        return view('marketplace.success', compact('order'));
    }

    /**
     * Ambil order yang boleh diakses user (dijaga oleh MarketplaceOrderPolicy).
     */
    private function findOwnedOrder($code)
    {
        $order = MarketplaceOrder::where('order_code', $code)
            ->with(['items.product', 'buyer'])
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
