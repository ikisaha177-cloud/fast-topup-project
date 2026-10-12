<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\OrderStatus;

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
        'payment_claimed_at',
        'confirmed_at',
        'confirmed_by',
        'confirmation_source',
        'failure_reason',
    ];

    protected $casts = [
        'total_amount'       => 'integer',
        'paid_at'            => 'datetime',
        'payment_claimed_at' => 'datetime',
        'confirmed_at'       => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items()
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    /**
     * Cek apakah pesanan sudah selesai.
     */
    public function isSuccess()
    {
        return $this->status === OrderStatus::SUCCESS;
    }

    /**
     * Cek apakah pesanan masih menunggu keputusan admin.
     */
    public function isPending()
    {
        return $this->status === OrderStatus::PENDING;
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

    /**
     * Dikonfirmasi otomatis oleh webhook gateway (bukan admin)?
     */
    public function confirmedByGateway()
    {
        return $this->confirmation_source === OrderStatus::SOURCE_GATEWAY;
    }

    /**
     * Label sumber konfirmasi untuk ditampilkan di UI.
     */
    public function confirmationLabel()
    {
        if ($this->confirmation_source === OrderStatus::SOURCE_GATEWAY) {
            return 'Otomatis (gateway pembayaran)';
        }

        if ($this->confirmation_source === OrderStatus::SOURCE_ADMIN) {
            return 'Admin' . ($this->confirmedBy ? ' · ' . $this->confirmedBy->name : '');
        }

        return '—';
    }
}
