<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'price',
        'compare_at_price',
        'stock',
        'image',
        'category',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'compare_at_price' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Apakah produk sedang diskon (harga coret lebih besar dari harga jual).
     */
    public function hasDiscount()
    {
        return $this->compare_at_price !== null
            && $this->compare_at_price > $this->price;
    }

    /**
     * Persentase diskon dibulatkan ke bawah, contoh: 4 untuk 48.000 dari 50.000.
     */
    public function discountPercent()
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        return (int) floor((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    /**
     * Jumlah potongan dalam rupiah.
     */
    public function discountAmount()
    {
        return $this->hasDiscount() ? $this->compare_at_price - $this->price : 0;
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orderItems()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    /**
     * Jumlah item yang benar-benar terjual (hanya dari pesanan berstatus selesai).
     *
     * Dipakai untuk menampilkan "Terjual N" — dihitung dari data nyata,
     * bukan angka statis. Pemanggil sebaiknya memakai withSoldCount()
     * supaya nilainya ikut ter-query (bukan satu query per produk).
     */
    public function getSoldCountAttribute()
    {
        if (array_key_exists('sold_count', $this->attributes)) {
            return (int) $this->attributes['sold_count'];
        }

        return (int) $this->soldItems()->sum('quantity');
    }

    /**
     * Item yang sudah terjual (pesanan berstatus Selesai).
     */
    public function soldItems()
    {
        return $this->hasMany(MarketplaceOrderItem::class)
            ->whereHas('order', function ($query) {
                $query->where('status', OrderStatus::SUCCESS);
            });
    }

    /**
     * Tambahkan kolom terhitung `sold_count` ke query agar tidak N+1.
     */
    public function scopeWithSoldCount($query)
    {
        return $query->addSelect([
            'sold_count' => MarketplaceOrderItem::query()
                ->selectRaw('COALESCE(SUM(quantity), 0)')
                ->whereColumn('marketplace_product_id', 'marketplace_products.id')
                ->whereHas('order', function ($sub) {
                    $sub->where('status', OrderStatus::SUCCESS);
                }),
        ]);
    }

    /**
     * Produk marketplace hanya boleh dihapus bila belum pernah dipesan,
     * supaya riwayat pesanan tidak ikut terhapus oleh cascade.
     */
    public function hasOrderItems()
    {
        return $this->orderItems()->exists();
    }
}
