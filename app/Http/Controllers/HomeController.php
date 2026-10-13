<?php

namespace App\Http\Controllers;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\OrderStatus;
use App\Support\PaymentMethods;

class HomeController extends Controller
{
    /**
     * Halaman utama website.
     */
    public function index()
    {
        $games = GameTitle::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $marketplaceProducts = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->with('seller')
            ->withSoldCount()
            ->latest()
            ->take(8)
            ->get();

        // Angka nyata untuk statistik hero (bukan hardcoded).
        $totalMarketplaceProducts = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->count();

        $paymentMethodCount = count(PaymentMethods::all());

        // Kartu melayang di hero harus menampilkan harga yang benar-benar ada
        // di katalog, supaya tidak ada nominal yang tidak cocok dengan produk.
        $heroProduct = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->with('gameTitle')
            ->whereHas('gameTitle', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('price')
            ->skip(2)
            ->first()
            ?? Product::where('is_active', true)->with('gameTitle')->orderBy('price')->first();

        // Kartu melayang hanya boleh menampilkan pesanan NYATA milik pengguna
        // yang sedang login. Guest / akun tanpa pesanan melihat pratinjau
        // katalog — tidak ada klaim "Pesanan Kamu" yang menyesatkan.
        $heroCard = $this->heroCard(auth()->user(), $heroProduct);

        return view('home', compact(
            'games',
            'marketplaceProducts',
            'heroCard',
            'totalMarketplaceProducts',
            'paymentMethodCount'
        ));
    }

    /**
     * Susun isi kartu melayang di hero.
     *
     * Prioritas: pesanan nyata terakhir milik pengguna (jujur, sesuai status
     * aslinya). Bila tidak ada (guest / belum pernah bertransaksi), kembalikan
     * pratinjau katalog yang jelas berlabel "Contoh".
     *
     * @param  \App\Models\User|null  $user
     * @param  \App\Models\Product|null  $heroProduct
     * @return array<string, mixed>
     */
    private function heroCard(?User $user, ?Product $heroProduct)
    {
        $real = $this->latestOrderFor($user);

        if ($real) {
            return $real;
        }

        return [
            'title' => 'Pratinjau Pesanan',
            'chip'  => ['text' => 'Contoh', 'class' => 'chip-demo'],
            'rows'  => $heroProduct ? [
                ['label' => 'Game', 'value' => $heroProduct->gameTitle->name ?? 'Game'],
                ['label' => 'Nominal', 'value' => $heroProduct->name],
                ['label' => 'Metode', 'value' => 'QRIS, e-wallet, transfer'],
            ] : [
                ['label' => 'Game', 'value' => 'Pilih game favoritmu'],
                ['label' => 'Nominal', 'value' => 'Pilih sesuai kebutuhan'],
                ['label' => 'Metode', 'value' => 'QRIS, e-wallet, transfer'],
            ],
            'total' => $heroProduct
                ? 'Rp ' . number_format($heroProduct->price, 0, ',', '.')
                : 'Bayar sesuai nominal',
        ];
    }

    /**
     * Pesanan terakhir milik pengguna (top up atau marketplace), mana yang
     * paling baru. Admin tidak diikutkan (tidak berbelanja).
     *
     * @param  \App\Models\User|null  $user
     * @return array<string, mixed>|null
     */
    private function latestOrderFor(?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return null;
        }

        $transaction = Transaction::where('user_id', $user->id)
            ->with(['gameTitle', 'product'])
            ->latest('created_at')
            ->first();

        $order = MarketplaceOrder::where('user_id', $user->id)
            ->with('items.product')
            ->latest('created_at')
            ->first();

        if (! $transaction && ! $order) {
            return null;
        }

        // Ambil yang paling baru di antara kedua jenis pesanan.
        $useTransaction = $order === null
            || ($transaction !== null && $transaction->created_at >= $order->created_at);

        return $useTransaction
            ? $this->transactionCard($transaction)
            : $this->marketplaceCard($order);
    }

    /**
     * Kartu dari transaksi top up nyata.
     *
     * @return array<string, mixed>
     */
    private function transactionCard(Transaction $transaction)
    {
        return [
            'title' => 'Pesanan Terakhir',
            'chip'  => $this->chipFor($transaction->status),
            'rows'  => [
                ['label' => 'Game', 'value' => $transaction->gameTitle->name ?? 'Game'],
                ['label' => 'Nominal', 'value' => $transaction->product->name ?? '-'],
                ['label' => 'Metode', 'value' => $transaction->payment_method],
            ],
            'total' => 'Rp ' . number_format((int) $transaction->amount, 0, ',', '.'),
        ];
    }

    /**
     * Kartu dari pesanan marketplace nyata.
     *
     * @return array<string, mixed>
     */
    private function marketplaceCard(MarketplaceOrder $order)
    {
        $count = $order->items->count();
        $first = $order->items->first();

        return [
            'title' => 'Pesanan Terakhir',
            'chip'  => $this->chipFor($order->status),
            'rows'  => [
                ['label' => 'Item', 'value' => $count . ' item'],
                ['label' => 'Produk', 'value' => $first && $first->product ? $first->product->name : 'Marketplace'],
                ['label' => 'Metode', 'value' => $order->payment_method],
            ],
            'total' => 'Rp ' . number_format((int) $order->total_amount, 0, ',', '.'),
        ];
    }

    /**
     * Chip status sesuai keadaan pesanan (jujur, bukan selalu "Selesai").
     *
     * @return array<string, string>
     */
    private function chipFor($status)
    {
        $class = [
            OrderStatus::SUCCESS   => 'chip-ok',
            OrderStatus::PENDING   => 'chip-warn',
            OrderStatus::FAILED    => 'chip-bad',
            OrderStatus::CANCELLED => 'chip-bad',
        ][$status] ?? 'chip-warn';

        return ['text' => OrderStatus::label($status), 'class' => $class];
    }
}
