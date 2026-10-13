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
        'promo_code',
        'discount_amount',
        'status',
        'expires_at',
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
        'total_amount'       => 'integer',
        'discount_amount'    => 'integer',
        'paid_at'            => 'datetime',
        'payment_claimed_at' => 'datetime',
        'confirmed_at'       => 'datetime',
        'revoked_at'         => 'datetime',
        'expires_at'         => 'datetime',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Admin yang membatalkan konfirmasi pesanan ini (bila ada).
     */
    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
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
     * Punya batas waktu pembayaran? (baris lama bisa saja tidak punya)
     */
    public function hasPaymentDeadline()
    {
        return $this->expires_at !== null;
    }

    /**
     * Pesanan masih menunggu pembayaran dan batas waktunya sudah lewat?
     *
     * Inilah syarat lazy expiry: sengaja tidak menangkap pesanan yang sudah
     * ditandai "sudah bayar" — uang mungkin sudah berpindah, jadi keputusannya
     * milik admin di antrean verifikasi, bukan milik jam.
     */
    public function paymentWindowExpired()
    {
        return $this->isPending()
            && ! $this->isPaymentClaimed()
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /**
     * Detik tersisa sebelum batas waktu pembayaran (0 bila sudah lewat / tidak ada).
     */
    public function secondsRemaining()
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return max(0, $this->expires_at->getTimestamp() - now()->getTimestamp());
    }

    /**
     * Pembeli sudah menandai "sudah bayar" (menunggu verifikasi admin)?
     */
    public function isPaymentClaimed()
    {
        return $this->payment_claimed_at !== null;
    }

    /**
     * Pesan jujur untuk pembeli yang mencoba membayar pesanan yang sudah tutup.
     */
    public function closedPaymentMessage()
    {
        if ($this->wasCancelledByExpiry()) {
            return 'Batas waktu pembayaran pesanan ini sudah habis, jadi pesanannya dibatalkan otomatis dan stoknya dikembalikan. Silakan buat pesanan baru.';
        }

        return 'Pesanan ini sudah ' . strtolower($this->statusLabel())
            . ', jadi tidak ada pembayaran yang perlu ditandai.';
    }

    /**
     * Dibatalkan otomatis oleh sistem karena kehabisan waktu bayar?
     */
    public function wasCancelledByExpiry()
    {
        return $this->status === OrderStatus::CANCELLED
            && $this->confirmation_source === OrderStatus::SOURCE_EXPIRY;
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

        if ($this->confirmation_source === OrderStatus::SOURCE_EXPIRY) {
            return 'Otomatis (batas waktu habis)';
        }

        if ($this->confirmation_source === OrderStatus::SOURCE_ADMIN) {
            return 'Admin' . ($this->confirmedBy ? ' · ' . $this->confirmedBy->name : '');
        }

        return '—';
    }
}
