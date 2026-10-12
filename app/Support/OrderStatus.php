<?php

namespace App\Support;

/**
 * Sumber tunggal untuk daftar status, label, badge, dan aturan transisi
 * pesanan (top up maupun marketplace).
 *
 * Alur bisnis:
 *   pending --(admin konfirmasi)--> success   : pesanan berhasil & selesai.
 *   pending --(admin tolak)-------> failed    : pesanan gagal, stok dikembalikan.
 *   pending --(pembeli batal)-----> cancelled : dibatalkan, stok dikembalikan.
 */
class OrderStatus
{
    public const PENDING   = 'pending';
    public const SUCCESS   = 'success';
    public const FAILED    = 'failed';
    public const CANCELLED = 'cancelled';

    /**
     * Semua status yang sah.
     *
     * @return array<int, string>
     */
    public static function all()
    {
        return [self::PENDING, self::SUCCESS, self::FAILED, self::CANCELLED];
    }

    /**
     * Label status yang ramah pengguna.
     *
     * @return array<string, string>
     */
    public static function labels()
    {
        return [
            self::PENDING   => 'Menunggu Konfirmasi',
            self::SUCCESS   => 'Selesai',
            self::FAILED    => 'Gagal',
            self::CANCELLED => 'Dibatalkan',
        ];
    }

    public static function label($status)
    {
        return self::labels()[$status] ?? ucfirst((string) $status);
    }

    /**
     * Class badge untuk tiap status.
     *
     * @return array<string, string>
     */
    public static function badges()
    {
        return [
            self::PENDING   => 'badge-pending',
            self::SUCCESS   => 'badge-success',
            self::FAILED    => 'badge-failed',
            self::CANCELLED => 'badge-cancelled',
        ];
    }

    public static function badge($status)
    {
        return self::badges()[$status] ?? 'badge-pending';
    }

    /**
     * Status yang boleh dicapai dari status saat ini.
     *
     * Status akhir (success/failed/cancelled) bersifat terminal: tidak bisa
     * diubah lagi, supaya stok dan laporan tetap konsisten.
     *
     * @return array<int, string>
     */
    public static function allowedTransitions($current)
    {
        if ($current === self::PENDING) {
            return [self::SUCCESS, self::FAILED, self::CANCELLED];
        }

        return [];
    }

    public static function canTransition($from, $to)
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    /**
     * Status akhir? (tidak ada perpindahan lagi)
     */
    public static function isFinal($status)
    {
        return in_array($status, [self::SUCCESS, self::FAILED, self::CANCELLED], true);
    }
}
