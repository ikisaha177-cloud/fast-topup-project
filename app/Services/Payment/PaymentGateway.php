<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;

/**
 * Kontrak gateway pembayaran.
 *
 * Semua driver (simulasi maupun Midtrans) harus memenuhi kontrak ini supaya
 * controller & service tidak perlu tahu detail provider.
 */
interface PaymentGateway
{
    /**
     * Nama driver, mis. "simulation" / "midtrans".
     */
    public function name(): string;

    /**
     * Buat permintaan pembayaran untuk sebuah referensi (kode transaksi/pesanan).
     *
     * @param  string  $reference  kode transaksi (TXN-...) atau pesanan (MKT-...)
     * @param  int     $amount     nominal dalam Rupiah
     * @param  string  $method     metode pembayaran yang dipilih user
     * @param  array<string, mixed>  $context  data tambahan (nama pembeli, dll)
     */
    public function createCharge(string $reference, int $amount, string $method, array $context = []): PaymentCharge;

    /**
     * Verifikasi + normalisasi payload webhook dari gateway.
     *
     * @throws \App\Services\Payment\Exceptions\InvalidWebhookSignature
     */
    public function parseWebhook(Request $request): WebhookEvent;
}
