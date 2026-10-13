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
        'promo_code',
        'discount_amount',
        'amount',
        'status',
        'paid_at',
        'payment_claimed_at',
        'payment_proof',
        'confirmed_at',
        'confirmed_by',
        'confirmation_source',
        'failure_reason',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'paid_at'            => 'datetime',
        'payment_claimed_at' => 'datetime',
        'confirmed_at'       => 'datetime',
        'revoked_at'         => 'datetime',
        'amount'             => 'integer',
        'discount_amount'    => 'integer',
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
     * Admin yang membatalkan konfirmasi transaksi ini (bila ada).
     */
    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Cek apakah transaksi sudah berhasil.
     */
    public function isSuccess()
    {
        return $this->status === OrderStatus::SUCCESS;
    }

    /**
     * Cek apakah transaksi masih menunggu keputusan admin.
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
     * Ada bukti pembayaran yang diunggah?
     */
    public function hasPaymentProof()
    {
        return ! empty($this->payment_proof);
    }

    /**
     * URL publik bukti pembayaran (via disk public).
     */
    public function paymentProofUrl()
    {
        return $this->hasPaymentProof()
            ? asset('storage/' . $this->payment_proof)
            : null;
    }

    /**
     * Perlu tindakan admin? (pending dan pembeli sudah menandai sudah bayar)
     */
    public function needsReview()
    {
        return $this->isPending() && $this->isPaymentClaimed();
    }

    /**
     * Bisa dibuka kembali oleh admin? (keputusan gagal/batal yang salah klik)
     */
    public function canBeReopened()
    {
        return OrderStatus::isReopenable($this->status);
    }

    /**
     * Bisa dibatalkan konfirmasinya oleh admin? (salah konfirmasi success)
     */
    public function canBeRevoked()
    {
        return OrderStatus::canRevoke($this->status);
    }

    /**
     * Konfirmasi pernah dibatalkan admin? (jejak audit)
     */
    public function wasRevoked()
    {
        return $this->revoked_at !== null;
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
     * Dikonfirmasi otomatis oleh sistem dari bukti/penandaan pembeli?
     */
    public function confirmedByAuto()
    {
        return $this->confirmation_source === OrderStatus::SOURCE_AUTO;
    }

    /**
     * Dikonfirmasi otomatis (gateway atau sistem), bukan admin?
     */
    public function confirmedAutomatically()
    {
        return $this->confirmedByGateway() || $this->confirmedByAuto();
    }

    /**
     * Label sumber konfirmasi untuk ditampilkan di UI.
     */
    public function confirmationLabel()
    {
        if ($this->confirmation_source === OrderStatus::SOURCE_GATEWAY) {
            return 'Otomatis (gateway pembayaran)';
        }

        if ($this->confirmation_source === OrderStatus::SOURCE_AUTO) {
            return 'Otomatis (bukti pembayaran)';
        }

        if ($this->confirmation_source === OrderStatus::SOURCE_ADMIN) {
            return 'Admin' . ($this->confirmedBy ? ' · ' . $this->confirmedBy->name : '');
        }

        return '—';
    }
}
