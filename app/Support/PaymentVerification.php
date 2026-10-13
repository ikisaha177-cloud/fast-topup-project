<?php

namespace App\Support;

/**
 * Sumber tunggal kebijakan verifikasi pembayaran (lihat config/payment.php).
 *
 * Dipakai bersama oleh alur top up & marketplace supaya aturan "kapan pesanan
 * boleh langsung Selesai tanpa admin" persis sama di kedua tempat.
 *
 *   hybrid (default) : ada bukti -> otomatis Selesai; tanpa bukti -> admin.
 *   manual           : selalu menunggu keputusan admin.
 *   auto             : setiap klaim "sudah bayar" -> otomatis Selesai.
 */
class PaymentVerification
{
    /** Selalu menunggu keputusan admin. */
    public const MANUAL = 'manual';

    /** Ada bukti pembayaran -> otomatis; tanpa bukti -> menunggu admin. */
    public const HYBRID = 'hybrid';

    /** Setiap klaim pembayaran -> otomatis Selesai. */
    public const AUTO = 'auto';

    /**
     * Semua mode yang sah.
     *
     * @return array<int, string>
     */
    public static function all()
    {
        return [self::MANUAL, self::HYBRID, self::AUTO];
    }

    /**
     * Mode yang sedang aktif (dinormalkan; nilai tak dikenal jatuh ke hybrid).
     */
    public static function mode()
    {
        $mode = (string) config('payment.verification', self::HYBRID);

        return in_array($mode, self::all(), true) ? $mode : self::HYBRID;
    }

    /**
     * Boleh langsung Selesai tanpa admin?
     *
     * @param  bool  $hasProof  pembeli mengunggah bukti pembayaran?
     */
    public static function autoConfirms($hasProof)
    {
        $mode = self::mode();

        if ($mode === self::AUTO) {
            return true;
        }

        // hybrid: hanya bukti pembayaran yang mengonfirmasi otomatis.
        return $mode === self::HYBRID && (bool) $hasProof;
    }
}
