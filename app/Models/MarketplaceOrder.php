<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'total_amount',
        'payment_method',
        'payment_provider',
        'payment_reference',
        'payment_url',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'paid_at'      => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }
}
