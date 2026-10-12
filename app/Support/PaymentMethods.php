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
        ];
    }

    public static function isQr(string $method)
    {
        return $method === self::QRIS;
    }
}
