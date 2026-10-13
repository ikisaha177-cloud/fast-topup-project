<?php

namespace App\Support;

/**
 * Jendela pembayaran — berapa lama pembeli punya waktu untuk menyelesaikan
 * pembayaran sebelum pesanan pending dibatalkan otomatis.
 *
 * Semua perhitungan batas waktu lewat kelas ini, supaya angka yang menentukan
 * "pesanan ini kedaluwarsa" selalu sama dengan angka yang ditampilkan ke
 * pembeli. Tidak ada cron: pembatalan terjadi saat pesanannya diakses
 * (lazy expiry) lewat {@see \App\Services\TransactionService::expireIfDue()}
 * dan {@see \App\Services\MarketplaceOrderService::expireIfDue()}.
 */
class PaymentWindow
{
    /** Batas bawah: terlalu pendek membuat pembeli nyaris pasti kehabisan waktu. */
    public const MIN_MINUTES = 5;

    /** Batas atas: satu minggu lebih mirip "tagihan" daripada batas bayar. */
    public const MAX_MINUTES = 10080;

    /**
     * Panjang jendela pembayaran, dalam menit.
     *
     * Nilai yang tidak masuk akal (negatif, nol, atau terlalu besar) dijepit
     * di sini agar salah set `.env` tidak pernah mengunci stok selamanya atau
     * membatalkan pesanan saat pembeli masih menekan tombol bayar.
     */
    public static function minutes(): int
    {
        $minutes = (int) config('payment.window', 180);

        if ($minutes < self::MIN_MINUTES) {
            return self::MIN_MINUTES;
        }

        return min($minutes, self::MAX_MINUTES);
    }

    /**
     * Batas waktu pembayaran untuk pesanan yang dibuat/dibuka ulang sekarang.
     *
     * @return \Illuminate\Support\Carbon
     */
    public static function deadline()
    {
        return now()->addMinutes(self::minutes());
    }

    /**
     * Durasi jendela dalam kata yang mudah dibaca, mis. "3 jam".
     */
    public static function label(): string
    {
        $minutes = self::minutes();

        if ($minutes % 1440 === 0) {
            $days = (int) ($minutes / 1440);

            return $days . ' hari';
        }

        if ($minutes % 60 === 0) {
            $hours = (int) ($minutes / 60);

            return $hours . ' jam';
        }

        return $minutes . ' menit';
    }

    /**
     * Alasan yang dicatat pada pesanan yang dibatalkan otomatis.
     *
     * Sengaja menyebut angka supaya pembeli paham aturannya, bukan sekadar
     * membaca "dibatalkan sistem".
     */
    public static function expiredReason(): string
    {
        return 'Pembayaran tidak diterima dalam batas waktu ' . self::label() . '.';
    }
}
