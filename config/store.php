<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kanal Bantuan Toko
    |--------------------------------------------------------------------------
    |
    | Nomor WhatsApp customer service dipakai untuk tombol chat di halaman
    | top up dan footer. Kosongkan bila tombol tidak ingin ditampilkan.
    |
    */

    'whatsapp' => env('STORE_WHATSAPP', '6281234567890'),

    'email' => env('STORE_EMAIL', 'support@gantinoid.test'),

    /*
    | Alamat email pengirim transaksional (verifikasi akun, notifikasi status).
    | Boleh berbeda dari email bantuan yang tampil di footer.
    */
    'support_email' => env('STORE_SUPPORT_EMAIL') ?: env('STORE_EMAIL', 'support@gantinoid.test'),
];
