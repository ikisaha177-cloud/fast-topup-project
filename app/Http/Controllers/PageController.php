<?php

namespace App\Http\Controllers;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;

/**
 * Halaman statis (kebijakan & bantuan) dan sitemap.xml.
 *
 * Halaman-halaman ini penting untuk kepercayaan pengguna sekaligus SEO:
 * memberi tahu aturan layanan, cara menangani data pribadi, dan langkah
 * pemesanan. Sitemap membantu mesin pencari menemukan halaman publik.
 */
class PageController extends Controller
{
    public function terms()
    {
        return view('pages.terms', ['updatedAt' => '2026-10-13']);
    }

    public function privacy()
    {
        return view('pages.privacy', ['updatedAt' => '2026-10-13']);
    }

    public function howToOrder()
    {
        return view('pages.how-to-order', ['updatedAt' => '2026-10-13']);
    }

    /**
     * Sitemap XML untuk halaman publik yang layak diindeks.
     *
     * Halaman privat (keranjang, transaksi, admin) sengaja tidak disertakan —
     * sejalan dengan aturan pada robots.txt.
     */
    public function sitemap()
    {
        $urls = [];

        $static = [
            [route('home'), '1.0', 'daily'],
            [route('topup.index'), '0.9', 'daily'],
            [route('marketplace.index'), '0.9', 'daily'],
            [route('page.how-to-order'), '0.6', 'monthly'],
            [route('page.terms'), '0.4', 'yearly'],
            [route('page.privacy'), '0.4', 'yearly'],
            [route('login'), '0.3', 'monthly'],
            [route('register'), '0.3', 'monthly'],
        ];

        foreach ($static as [$loc, $priority, $freq]) {
            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => now()->toAtomString(),
                'changefreq' => $freq,
                'priority'   => $priority,
            ];
        }

        // Halaman detail tiap game yang aktif.
        foreach (GameTitle::where('is_active', true)->orderBy('name')->get() as $game) {
            $urls[] = [
                'loc'        => route('topup.show', $game->slug),
                'lastmod'    => ($game->updated_at ?? now())->toAtomString(),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        // Halaman detail tiap produk marketplace yang aktif & masih ada stok.
        $products = MarketplaceProduct::where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            $urls[] = [
                'loc'        => route('marketplace.show', $product->slug),
                'lastmod'    => ($product->updated_at ?? now())->toAtomString(),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }

        $xml = view('pages.sitemap', compact('urls'))->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
