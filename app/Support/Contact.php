<?php

namespace App\Support;

/**
 * Kanal bantuan toko. Semuanya dibaca dari config/store.php supaya nomor
 * CS bisa diganti lewat .env tanpa menyentuh view.
 */
class Contact
{
    /**
     * Nomor WhatsApp dalam digit saja (tanpa +, spasi, atau tanda hubung).
     */
    public static function whatsappNumber(): string
    {
        return (string) preg_replace('/\D/', '', (string) config('store.whatsapp', ''));
    }

    /**
     * Tautan wa.me dengan pesan pembuka. Mengembalikan null bila nomor
     * belum diisi â€” tautan rusak tidak boleh ditampilkan ke pengunjung.
     */
    public static function whatsappUrl(string $context = ''): ?string
    {
        $number = self::whatsappNumber();

        if ($number === '') {
            return null;
        }

        $message = trim('Halo FastTopup Store. ' . $context);

        return 'https://wa.me/' . $number
            . ($message !== '' ? '?text=' . rawurlencode($message) : '');
    }

    /**
     * Alamat email customer service.
     */
    public static function email(): string
    {
        return (string) config('store.email', 'support@FastTopup.test');
    }

    /**
     * Alamat pengirim/balasan email transaksional (verifikasi akun, notifikasi).
     * Jatuh kembali ke email bantuan bila belum diatur agar tidak pernah kosong.
     */
    public static function supportEmail(): string
    {
        $email = trim((string) config('store.support_email', ''));

        return $email !== '' ? $email : self::email();
    }
}

