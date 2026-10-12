<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan webhook pembayaran yang sudah diproses.
 *
 * event_id unik => webhook yang dikirim berulang otomatis ditolak,
 * sehingga efek samping (ubah status, kembalikan stok, kirim email)
 * tidak terjadi dua kali.
 */
class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'provider',
        'reference',
        'status',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];
}
