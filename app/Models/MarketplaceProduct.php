<?php

namespace App\Models;

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
     * Produk marketplace hanya boleh dihapus bila belum pernah dipesan,
     * supaya riwayat pesanan tidak ikut terhapus oleh cascade.
     */
    public function hasOrderItems()
    {
        return $this->orderItems()->exists();
    }
}
