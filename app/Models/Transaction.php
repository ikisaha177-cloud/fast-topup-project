<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'user_id',
        'game_title_id',
        'product_id',
        'user_game_id',
        'server_id',
        'payment_method',
        'payment_provider',
        'payment_reference',
        'payment_url',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameTitle()
    {
        return $this->belongsTo(GameTitle::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Cek apakah transaksi sudah berhasil.
     */
    public function isSuccess()
    {
        return $this->status === 'success';
    }
}
