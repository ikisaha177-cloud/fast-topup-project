<?php

namespace App\Support;

/**
 * Instruksi pembayaran per metode (QRIS, e-wallet, transfer bank) untuk
 * portal pembayaran simulasi.
 *
 * Nomor Virtual Account / nomor e-wallet diturunkan secara DETERMINISTIK dari
 * kode transaksi, jadi halaman yang sama selalu menampilkan angka yang sama —
 * tanpa panggilan jaringan, dan bisa diuji.
 *
 * Semua angka di sini adalah SIMULASI (lihat config payment.driver =
 * "simulation"); tidak ada uang yang benar-benar berpindah.
 */
class PaymentChannel
{
    public const TYPE_QR      = 'qr';
    public const TYPE_EWALLET = 'ewallet';
    public const TYPE_BANK    = 'bank';

    /**
     * Susun instruksi pembayaran untuk satu metode.
     *
     * @return array<string, mixed>
     */
    public static function forMethod(string $method, string $code, int $amount): array
    {
        if ($method === PaymentMethods::QRIS) {
            return self::qris($amount);
        }

        if ($method === PaymentMethods::BANK_TRANSFER) {
            return self::bank($code, $amount);
        }

        return self::ewallet($method, $code, $amount);
    }

    /**
     * QRIS: satu kode QR untuk semua bank & e-wallet.
     *
     * @return array<string, mixed>
     */
    private static function qris(int $amount): array
    {
        return [
            'type'          => self::TYPE_QR,
            'label'         => PaymentMethods::QRIS,
            'headline'      => 'Pindai kode QR',
            'note'          => 'Satu kode untuk semua bank dan e-wallet. Nominal sudah terisi otomatis.',
            'account'       => null,
            'account_label' => null,
            'steps'         => [
                'Buka aplikasi bank atau e-wallet pilihanmu.',
                'Pilih menu Bayar / Pindai QRIS, lalu arahkan kamera ke kode QR di atas.',
                'Periksa nama toko & nominal Rp ' . number_format($amount, 0, ',', '.') . ', lalu selesaikan pembayaran.',
            ],
        ];
    }

    /**
     * Transfer bank: Virtual Account 12 digit.
     *
     * @return array<string, mixed>
     */
    private static function bank(string $code, int $amount): array
    {
        return [
            'type'          => self::TYPE_BANK,
            'label'         => PaymentMethods::BANK_TRANSFER,
            'headline'      => 'Transfer ke Virtual Account',
            'note'          => 'Nomor Virtual Account ini khusus untuk pesananmu. Transfer dari m-banking mana pun.',
            'account'       => self::virtualAccount($code),
            'account_label' => 'Nomor Virtual Account',
            'steps'         => [
                'Buka m-banking, lalu pilih menu Transfer ke Virtual Account.',
                'Masukkan nomor Virtual Account di atas.',
                'Transfer tepat sebesar Rp ' . number_format($amount, 0, ',', '.') . ' agar pesanan otomatis terdeteksi.',
            ],
        ];
    }

    /**
     * E-wallet (DANA / GoPay / OVO): kirim ke nomor tujuan.
     *
     * @return array<string, mixed>
     */
    private static function ewallet(string $method, string $code, int $amount): array
    {
        return [
            'type'          => self::TYPE_EWALLET,
            'label'         => $method,
            'headline'      => 'Bayar lewat ' . $method,
            'note'          => 'Buka aplikasi ' . $method . ', lalu kirim ke nomor tujuan berikut.',
            'account'       => self::ewalletNumber($code),
            'account_label' => 'Nomor Tujuan ' . $method,
            'steps'         => [
                'Buka aplikasi ' . $method . ' di ponselmu.',
                'Pilih Kirim / Transfer, lalu masukkan nomor tujuan di atas.',
                'Kirim tepat sebesar Rp ' . number_format($amount, 0, ',', '.') . ' dan simpan struk yang muncul.',
            ],
        ];
    }

    /**
     * Nomor Virtual Account simulasi (16 digit, berawalan 8808).
     */
    public static function virtualAccount(string $code): string
    {
        $digits = str_pad((string) (self::seed($code) % 100000000), 8, '0', STR_PAD_LEFT);

        return '8808 ' . substr($digits, 0, 4) . ' ' . substr($digits, 4, 4);
    }

    /**
     * Nomor e-wallet simulasi (berawalan 08).
     */
    public static function ewalletNumber(string $code): string
    {
        $digits = str_pad((string) (self::seed($code) % 1000000000), 10, '0', STR_PAD_LEFT);

        return '08' . substr($digits, 0, 2) . '-' . substr($digits, 2, 4) . '-' . substr($digits, 6, 4);
    }

    /**
     * Benih deterministik dari kode transaksi (crc32 selalu positif).
     */
    private static function seed(string $code): int
    {
        return (int) sprintf('%u', crc32($code));
    }
}
