<?php

namespace App\Support;

/**
 * Instruksi pembayaran per metode (QRIS, e-wallet, transfer bank, VA per
 * bank, dan retail Alfamart/Indomaret) untuk portal pembayaran simulasi.
 *
 * Nomor Virtual Account / nomor e-wallet / kode pembayaran retail diturunkan
 * secara DETERMINISTIK dari kode transaksi, jadi halaman yang sama selalu
 * menampilkan angka yang sama — tanpa panggilan jaringan, dan bisa diuji.
 *
 * Semua angka di sini adalah SIMULASI (lihat config payment.driver =
 * "simulation"); tidak ada uang yang benar-benar berpindah.
 */
class PaymentChannel
{
    public const TYPE_QR      = 'qr';
    public const TYPE_EWALLET = 'ewallet';
    public const TYPE_BANK    = 'bank';
    public const TYPE_RETAIL  = 'retail';

    /**
     * Awalan nomor Virtual Account per metode (angka simulasi).
     */
    private const VA_PREFIX = [
        PaymentMethods::BANK_TRANSFER => '8808',
        PaymentMethods::BANK_BCA      => '3901',
        PaymentMethods::BANK_MANDIRI  => '8890',
    ];

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

        if ($method === PaymentMethods::ALFAMART || $method === PaymentMethods::INDOMARET) {
            return self::retail($method, $code, $amount);
        }

        if (isset(self::VA_PREFIX[$method])) {
            return self::bank($method, $code, $amount);
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
     * Transfer bank: Virtual Account (awalan nomor menyesuaikan metode).
     */
    private static function bank(string $method, string $code, int $amount): array
    {
        $named = $method !== PaymentMethods::BANK_TRANSFER;
        $bank = str_replace(' Virtual Account', '', $method);

        return [
            'type'          => self::TYPE_BANK,
            'label'         => $method,
            'headline'      => 'Transfer ke Virtual Account' . ($named ? ' ' . $bank : ''),
            'note'          => $named
                ? 'Nomor Virtual Account ' . $bank . ' ini khusus untuk pesananmu.'
                : 'Nomor Virtual Account ini khusus untuk pesananmu. Transfer dari m-banking mana pun.',
            'account'       => self::virtualAccount($code, self::VA_PREFIX[$method]),
            'account_label' => 'Nomor Virtual Account',
            'steps'         => array_merge(
                $named
                    ? ['Buka m-banking ' . $bank . ', lalu pilih menu Transfer ke Virtual Account.']
                    : ['Buka m-banking, lalu pilih menu Transfer ke Virtual Account.'],
                [
                    'Masukkan nomor Virtual Account di atas.',
                    'Transfer tepat sebesar Rp ' . number_format($amount, 0, ',', '.') . ' agar pesanan otomatis terdeteksi.',
                ]
            ),
        ];
    }

    /**
     * Alfamart / Indomaret: kode pembayaran dibayar tunai di kasir.
     */
    private static function retail(string $method, string $code, int $amount): array
    {
        return [
            'type'          => self::TYPE_RETAIL,
            'label'         => $method,
            'headline'      => 'Bayar tunai di ' . $method,
            'note'          => 'Datang ke gerai ' . $method . ' terdekat, lalu sebutkan kode pembayaran ini ke kasir.',
            'account'       => self::paymentCode($code),
            'account_label' => 'Kode Pembayaran',
            'steps'         => [
                'Datang ke gerai ' . $method . ' terdekat yang sedang buka.',
                'Minta kasir membayar kode pembayaran di atas (menu Bayar / Top Up).',
                'Bayar tunai tepat Rp ' . number_format($amount, 0, ',', '.') . ' dan simpan struk dari kasir.',
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
     * Nomor Virtual Account simulasi (12 digit, awalan menyesuaikan metode).
     */
    public static function virtualAccount(string $code, string $prefix = '8808'): string
    {
        $digits = str_pad((string) (self::seed($code) % 100000000), 8, '0', STR_PAD_LEFT);

        return $prefix . ' ' . substr($digits, 0, 4) . ' ' . substr($digits, 4, 4);
    }

    /**
     * Kode pembayaran retail simulasi (12 digit) untuk Alfamart / Indomaret.
     */
    public static function paymentCode(string $code): string
    {
        $digits = str_pad((string) (self::seed($code) % 1000000000000), 12, '0', STR_PAD_LEFT);

        return substr($digits, 0, 4) . ' ' . substr($digits, 4, 4) . ' ' . substr($digits, 8, 4);
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
