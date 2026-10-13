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
 *   success --(admin batalkan konfirmasi)--> failed  : salah konfirmasi
 *                                                      diperbaiki; stok kembali.
 *
 * Perbedaan jalur menuju `success` dicatat pada kolom `confirmation_source`
 * (admin|gateway|auto) supaya jejak audit tetap jujur: siapa yang benar-benar
 * menyelesaikan pesanan.
 *
 * Catatan: `success` bukan lagi jalan buntu. Admin boleh membatalkan
 * konfirmasi yang salah (success -> failed); stok dikembalikan dan alasan
 * pembatalan dicatat. Ini satu-satunya jalan keluar dari `success`.
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
     * Konfirmasi otomatis oleh sistem dari penandaan/bukti pembayaran pembeli
     * (kebijakan verifikasi hybrid/auto — lihat {@see PaymentVerification}).
     */
    public const SOURCE_AUTO = 'auto';

    /**
     * Pembatalan otomatis oleh sistem karena batas waktu pembayaran lewat
     * (lazy expiry — lihat {@see PaymentWindow}).
     */
    public const SOURCE_EXPIRY = 'expiry';

    /**
     * Semua sumber keputusan yang sah.
     *
     * @return array<int, string>
     */
    public static function sources()
    {
        return [self::SOURCE_ADMIN, self::SOURCE_GATEWAY, self::SOURCE_AUTO, self::SOURCE_EXPIRY];
    }

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
     * Sebagian besar status akhir bersifat terminal. Pengecualian: `success`
     * boleh turun ke `failed` bila admin salah mengonfirmasi (lihat
     * {@see revocable()}).
     *
     * @return array<int, string>
     */
    public static function allowedTransitions($current)
    {
        if ($current === self::PENDING) {
            return [self::SUCCESS, self::FAILED, self::CANCELLED];
        }

        if ($current === self::SUCCESS) {
            // Batalkan konfirmasi yang salah: pesanan selesai dikembalikan
            // menjadi gagal. Stok ikut dikembalikan oleh service.
            return [self::FAILED];
        }

        return [];
    }

    public static function canTransition($from, $to)
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    /**
     * Status akhir? (tidak ada perpindahan normal lagi)
     *
     * Catatan: `success` masih bisa dibatalkan admin (success -> failed) dan
     * `failed`/`cancelled` bisa dibuka kembali — keduanya lewat jalur admin
     * khusus, bukan transisi normal. Jadi `isFinal` dipakai untuk memutuskan
     * pesan, bukan sebagai pagar keras.
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
     * Status yang boleh dibatalkan konfirmasinya oleh admin (salah konfirmasi).
     *
     * Hanya `success`: pembatalan mengembalikan stok dan menurunkan status ke
     * `failed`, sehingga tidak boleh dipakai untuk membatalkan keputusan gagal.
     *
     * @return array<int, string>
     */
    public static function revocable()
    {
        return [self::SUCCESS];
    }

    public static function canRevoke($status)
    {
        return in_array($status, self::revocable(), true);
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
