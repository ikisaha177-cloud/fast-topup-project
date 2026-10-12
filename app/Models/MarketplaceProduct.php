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
        'stock',
        'image',
        'category',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

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
