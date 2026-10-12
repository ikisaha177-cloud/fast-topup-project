<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\OrderStatus;

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
        'payment_claimed_at',
        'confirmed_at',
        'confirmed_by',
        'failure_reason',
    ];

    protected $casts = [
        'paid_at'            => 'datetime',
        'payment_claimed_at' => 'datetime',
        'confirmed_at'       => 'datetime',
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
     * Admin yang memverifikasi transaksi ini.
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Cek apakah transaksi sudah berhasil.
     */
    public function isSuccess()
    {
        return $this->status === 'success';
    }

    /**
     * Cek apakah transaksi masih menunggu keputusan admin.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Pembeli sudah menandai "sudah bayar" (menunggu verifikasi admin)?
     */
    public function isPaymentClaimed()
    {
        return $this->payment_claimed_at !== null;
    }

    /**
     * Perlu tindakan admin? (pending dan pembeli sudah menandai sudah bayar)
     */
    public function needsReview()
    {
        return $this->isPending() && $this->isPaymentClaimed();
    }

    /**
     * Label status yang ramah pengguna, mis. "Menunggu Konfirmasi".
     */
    public function statusLabel()
    {
        return OrderStatus::label($this->status);
    }

    /**
     * Class badge untuk status saat ini.
     */
    public function statusBadge()
    {
        return OrderStatus::badge($this->status);
    }
}
