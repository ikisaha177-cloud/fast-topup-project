<?php

namespace App\Support;

use App\Models\MarketplaceOrder;
use App\Models\Review;

/**
 * Feed "aktivitas terbaru" publik: ulasan & pembelian sukses terbaru,
 * dengan identitas pembeli yang disamarkan (lihat User::publicName()).
 *
 * Setiap baris berasal dari data nyata di tabel reviews /
 * marketplace_orders — tidak ada aktivitas yang dikarang.
 */
class ActivityFeed
{
    /**
     * @return array<int, array{label: string, text: string, url: string|null, time: string}>
     */
    public static function items(int $limit = 6): array
    {
        $reviews = Review::with(['user', 'product:id,name,slug'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function (Review $review) {
                $product = $review->product;

                return [
                    'label' => $review->reviewerLabel(),
                    'text'  => 'memberi ulasan ' . $review->rating . ' bintang untuk '
                        . ($product !== null ? $product->name : 'sebuah produk'),
                    'url'   => $product !== null ? route('marketplace.show', $product->slug) : null,
                    'at'    => $review->created_at,
                ];
            });

        $orders = MarketplaceOrder::where('status', 'success')
            ->with(['buyer', 'items.product:id,name,slug'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function (MarketplaceOrder $order) {
                $item = $order->items->first();
                $product = $item !== null ? $item->product : null;

                return [
                    'label' => $order->buyer !== null ? $order->buyer->publicName() : 'Pembeli',
                    'text'  => 'memesan ' . ($product !== null ? $product->name : 'sebuah produk'),
                    'url'   => $product !== null ? route('marketplace.show', $product->slug) : null,
                    'at'    => $order->created_at,
                ];
            });

        return $reviews->concat($orders)
            ->sortByDesc(function (array $entry) {
                return $entry['at']->getTimestamp();
            })
            ->take($limit)
            ->values()
            ->map(function (array $entry) {
                $entry['time'] = self::ago($entry['at']);

                return $entry;
            })
            ->all();
    }

    /**
     * Waktu relatif ringkas dalam bahasa Indonesia
     * ("Baru saja", "12 menit lalu", "3 jam lalu", "2 hari lalu").
     */
    private static function ago($then): string
    {
        $seconds = max(0, time() - $then->getTimestamp());

        if ($seconds < 60) {
            return 'Baru saja';
        }

        if ($seconds < 3600) {
            return ((int) floor($seconds / 60)) . ' menit lalu';
        }

        if ($seconds < 86400) {
            return ((int) floor($seconds / 3600)) . ' jam lalu';
        }

        return ((int) floor($seconds / 86400)) . ' hari lalu';
    }
}
