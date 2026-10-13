<?php

namespace App\Http\Controllers;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;
use App\Models\Product;
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

        return view('home', compact(
            'games',
            'marketplaceProducts',
            'heroProduct',
            'totalMarketplaceProducts',
            'paymentMethodCount'
        ));
    }
}
