<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Review;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Ulasan produk marketplace dari pembelian terverifikasi.
 *
 * Batas kepercayaan ada di sini: ulasan hanya boleh ditulis bila
 * (1) pembeli benar-benar punya pesanan itu,
 * (2) pesanannya berstatus Selesai, dan
 * (3) produk yang diulas memang bagian dari pesanan tersebut.
 * Ketiganya diperiksa ulang setiap kali formulir dikirim, bukan cuma
 * disembunyikan di tampilan.
 *
 * Satu akun hanya boleh punya satu ulasan per produk (kunci unik di tabel),
 * jadi formulir kedua berfungsi sebagai "perbarui ulasan" — rating tidak bisa
 * digelembungkan dengan membeli produk yang sama berulang kali.
 */
class ReviewController extends Controller
{
    /**
     * Simpan atau perbarui ulasan satu item pada pesanan yang sudah Selesai.
     */
    public function store(Request $request, $code)
    {
        $data = $request->validate([
            'marketplace_product_id' => ['required', 'integer'],
            'rating'                 => ['required', 'integer', Rule::in(Review::allowedRatings())],
            'comment'                => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Pilih dulu rating bintangnya.',
            'rating.in'       => 'Rating harus bernilai 1 sampai 5 bintang.',
            'comment.max'     => 'Komentar maksimal 1000 karakter.',
        ]);

        $order = MarketplaceOrder::where('order_code', $code)
            ->with('items')
            ->firstOrFail();

        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $order->isSuccess()) {
            return back()->with('error', 'Ulasan hanya bisa dikirim untuk pesanan yang sudah Selesai.');
        }

        $item = $order->items->firstWhere('marketplace_product_id', (int) $data['marketplace_product_id']);

        if (! $item || ! $item->product) {
            return back()->with('error', 'Produk ini bukan bagian dari pesanan tersebut.');
        }

        $product = $item->product;
        $comment = trim((string) ($data['comment'] ?? ''));

        $review = Review::updateOrCreate(
            [
                'user_id'                => auth()->id(),
                'marketplace_product_id' => $product->id,
            ],
            [
                'marketplace_order_id' => $order->id,
                'rating'               => (int) $data['rating'],
                'comment'              => $comment === '' ? null : $comment,
            ]
        );

        ActivityLogger::log(
            $review->wasRecentlyCreated ? 'review.create' : 'review.update',
            ($review->wasRecentlyCreated ? 'Ulasan baru' : 'Ulasan diperbarui')
                . ' (' . $review->rating . '/5) untuk produk ' . $product->name . '.',
            $review
        );

        return back()->with(
            'success',
            $review->wasRecentlyCreated
                ? 'Terima kasih! Ulasanmu sudah tersimpan.'
                : 'Ulasanmu sudah diperbarui.'
        );
    }
}
