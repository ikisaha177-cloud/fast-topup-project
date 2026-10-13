<?php

namespace App\Support;

/**
 * Sumber tunggal untuk daftar status, label, badge, dan aturan transisi
 * pesanan (top up maupun marketplace).
 *
 * Alur bisnis:
 *   pending --(admin setujui)------------> success   : pesanan selesai.
 *   pending --(webhook gateway sukses)--> success   : pembayaran digital
 *                                                      terverifikasi otomatis.
 *   pending --(admin tolak)-------------> failed    : gagal, stok dikembalikan.
 *   pending --(pembeli batal)-----------> cancelled : dibatalkan, stok kembali.
 *
 *   failed/cancelled --(admin buka ulang)--> pending : salah klik diperbaiki;
 *                                                      stok dikunci ulang.
 *
 * Perbedaan dua jalur menuju `success` dicatat pada kolom
 * `confirmation_source` (admin|gateway) supaya jejak audit tetap jujur.
 */
class OrderStatus
{
    public const PENDING   = 'pending';
    public const SUCCESS   = 'success';
    public const FAILED    = 'failed';
    public const CANCELLED = 'cancelled';

    /** Konfirmasi dilakukan admin (verifikasi manual). */
    public const SOURCE_ADMIN = 'admin';

    /** Konfirmasi dilakukan otomatis oleh webhook gateway pembayaran. */
    public const SOURCE_GATEWAY = 'gateway';

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
        if (self::isFinal($current)) {
            return [];
        }

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

    /**
     * Keputusan yang boleh diambil admin pada halaman verifikasi.
     *
     * Hanya dua: setujui (success) atau tolak (failed). Pembatalan adalah
     * tindakan pembeli, bukan admin — jadi sengaja tidak disertakan agar
     * aturan validasi persis sama dengan tombol yang tampil di UI.
     *
     * @return array<int, string>
     */
    public static function adminDecisions()
    {
        return [self::SUCCESS, self::FAILED];
    }

    /**
     * Status yang boleh dibuka kembali oleh admin (salah klik).
     *
     * Hanya failed & cancelled — keduanya mengembalikan stok saat diputuskan,
     * sehingga membukanya kembali wajib mengunci ulang stok.
     *
     * @return array<int, string>
     */
    public static function reopenable()
    {
        return [self::FAILED, self::CANCELLED];
    }

    public static function isReopenable($status)
    {
        return in_array($status, self::reopenable(), true);
    }

    /**
     * Label untuk langkah terakhir pada indikator progres.
     *
     * Selama pesanan belum diputuskan (pending) langkah terakhir masih berupa
     * target, jadi tetap bernama "Selesai". Baru setelah gagal/dibatalkan
     * labelnya berubah supaya indikator tidak menyesatkan pembeli.
     */
    public static function finalStepLabel($status)
    {
        return in_array($status, [self::FAILED, self::CANCELLED], true)
            ? self::label($status)
            : self::labels()[self::SUCCESS];
    }
}
