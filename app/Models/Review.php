<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Ulasan pembeli untuk sebuah produk marketplace.
 *
 * Ulasan tidak pernah dikarang: baris ini hanya dibuat lewat
 * {@see \App\Http\Controllers\ReviewController} yang lebih dulu memastikan
 * pembelinya benar-benar punya pesanan berstatus Selesai atas produk itu.
 */
class Review extends Model
{
    use HasFactory;

    /** Batas rating yang sah (inklusif). */
    public const MIN_RATING = 1;
    public const MAX_RATING = 5;

    protected $fillable = [
        'marketplace_product_id',
        'user_id',
        'marketplace_order_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Rating yang boleh dipilih pembeli, dari tertinggi ke terendah
     * (urutan tampil di form).
     *
     * @return array<int, int>
     */
    public static function ratings()
    {
        return range(self::MAX_RATING, self::MIN_RATING);
    }

    /**
     * Daftar rating untuk aturan validasi.
     *
     * @return array<int, int>
     */
    public static function allowedRatings()
    {
        return range(self::MIN_RATING, self::MAX_RATING);
    }

    public function product()
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    /**
     * Nama penulis ulasan, disamarkan sebagian.
     *
     * Halaman produk bisa dibuka siapa saja, jadi nama lengkap pembeli
     * tidak ditampilkan mentah — cukup "Budi S." supaya tetap terasa
     * manusiawi tanpa membocorkan identitas.
     */
    public function reviewerLabel()
    {
        $name = trim((string) ($this->user->name ?? ''));

        if ($name === '') {
            return 'Pembeli';
        }

        $parts = preg_split('/\s+/', $name, 2);

        if (count($parts) < 2) {
            return Str::limit($parts[0], 18, '');
        }

        return $parts[0] . ' ' . Str::upper(Str::substr($parts[1], 0, 1)) . '.';
    }

    /**
     * Apakah ulasan sudah pernah disunting penulisnya.
     */
    public function wasEdited()
    {
        return $this->updated_at !== null
            && $this->created_at !== null
            && $this->updated_at->gt($this->created_at);
    }
}
