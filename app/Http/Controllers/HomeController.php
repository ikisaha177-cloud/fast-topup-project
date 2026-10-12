<?php

namespace App\Http\Controllers;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;

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
            ->latest()
            ->take(8)
            ->get();

        return view('home', compact('games', 'marketplaceProducts'));
    }
}
