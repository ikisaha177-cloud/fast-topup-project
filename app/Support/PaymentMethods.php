<?php

namespace App\Support;

/**
 * Daftar metode pembayaran yang didukung (simulasi).
 * Satu sumber kebenaran supaya validasi & tampilan tidak pernah beda.
 */
class PaymentMethods
{
    public const QRIS = 'QRIS';
    public const DANA = 'DANA';
    public const GOPAY = 'GoPay';
    public const OVO = 'OVO';
    public const BANK_TRANSFER = 'Bank Transfer';
    public const BANK_BCA = 'BCA Virtual Account';
    public const BANK_MANDIRI = 'Mandiri Virtual Account';
    public const ALFAMART = 'Alfamart';
    public const INDOMARET = 'Indomaret';

    /**
     * Semua metode pembayaran.
     *
     * @return array<int, string>
     */
    public static function all()
    {
        return [
            self::QRIS,
            self::DANA,
            self::GOPAY,
            self::OVO,
            self::BANK_TRANSFER,
            self::BANK_BCA,
            self::BANK_MANDIRI,
            self::ALFAMART,
            self::INDOMARET,
        ];
    }

    /**
     * Metode + keterangan singkat untuk ditampilkan di halaman checkout.
     *
     * @return array<string, string>
     */
    public static function labelled()
    {
        return [
            self::QRIS          => 'Scan kode QR, semua e-wallet & mobile banking.',
            self::DANA          => 'Bayar lewat aplikasi DANA.',
            self::GOPAY         => 'Bayar lewat aplikasi Gojek / GoPay.',
            self::OVO           => 'Bayar lewat aplikasi OVO.',
            self::BANK_TRANSFER => 'Transfer manual ke rekening virtual.',
            self::BANK_BCA      => 'Virtual Account khusus BCA.',
            self::BANK_MANDIRI  => 'Virtual Account khusus Mandiri.',
            self::ALFAMART      => 'Bayar tunai di kasir Alfamart.',
            self::INDOMARET     => 'Bayar tunai di kasir Indomaret.',
        ];
    }

    public static function isQr(string $method)
    {
        return $method === self::QRIS;
    }
}
