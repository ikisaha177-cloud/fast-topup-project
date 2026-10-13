<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kode promo yang bisa dipakai pembeli di checkout top up & marketplace.
 */
class PromoCode extends Model
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed';

    public const SCOPE_ALL         = 'all';
    public const SCOPE_TOPUP       = 'topup';
    public const SCOPE_MARKETPLACE = 'marketplace';

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'usage_limit',
        'used_count',
        'scope',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    /**
     * Kode promo selalu disimpan huruf besar + tanpa spasi, apa pun jalur masuknya.
     *
     * @param  string  $value
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    protected $casts = [
        'value'        => 'integer',
        'min_spend'    => 'integer',
        'max_discount' => 'integer',
        'usage_limit'  => 'integer',
        'used_count'   => 'integer',
        'is_active'    => 'boolean',
        'starts_at'    => 'datetime',
        'ends_at'      => 'datetime',
    ];

    /**
     * Potongan rupiah untuk subtotal tertentu.
     *
     * Selalu dijaga: tidak pernah negatif dan tidak pernah melebihi subtotal,
     * jadi total bayar tidak mungkin jadi minus.
     */
    public function discountFor($amount)
    {
        $amount = (int) $amount;

        $discount = $this->type === self::TYPE_PERCENT
            ? (int) floor($amount * $this->value / 100)
            : (int) $this->value;

        if ($this->max_discount !== null) {
            $discount = min($discount, (int) $this->max_discount);
        }

        return max(0, min($discount, $amount));
    }

    /**
     * Kuota pemakaian sudah habis?
     */
    public function isUsedUp()
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Sudah mulai berlaku?
     */
    public function hasStarted()
    {
        return $this->starts_at === null || now()->gte($this->starts_at);
    }

    /**
     * Sudah kedaluwarsa?
     */
    public function hasExpired()
    {
        return $this->ends_at !== null && now()->gt($this->ends_at);
    }

    /**
     * Kode promo berlaku untuk layanan ini?
     */
    public function coversScope($scope)
    {
        return $this->scope === self::SCOPE_ALL || $this->scope === $scope;
    }

    /**
     * Label singkat nilai promo untuk ditampilkan di admin, mis. "10%" atau "Rp 5.000".
     */
    public function valueLabel()
    {
        if ($this->type === self::TYPE_PERCENT) {
            return $this->value . '%';
        }

        return 'Rp ' . number_format((int) $this->value, 0, ',', '.');
    }

    /**
     * Label cakupan yang ramah pengguna.
     */
    public function scopeLabel()
    {
        if ($this->scope === self::SCOPE_TOPUP) {
            return 'Top Up Game';
        }

        if ($this->scope === self::SCOPE_MARKETPLACE) {
            return 'Marketplace';
        }

        return 'Semua';
    }

    /**
     * Status yang bisa dibaca admin (bukan hanya kolom is_active).
     */
    public function statusLabel()
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }

        if (! $this->hasStarted()) {
            return 'Terjadwal';
        }

        if ($this->hasExpired()) {
            return 'Kedaluwarsa';
        }

        if ($this->isUsedUp()) {
            return 'Kuota habis';
        }

        return 'Aktif';
    }

    /**
     * Class badge status untuk tabel admin.
     */
    public function statusBadge()
    {
        return $this->statusLabel() === 'Aktif' ? 'badge-success' : 'badge-muted';
    }
}
