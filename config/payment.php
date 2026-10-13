<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Driver pembayaran yang aktif.
    |
    | - "simulation" : pembayaran simulasi (pseudo-QR), tanpa panggilan jaringan.
    |                  Cocok untuk demo & testing.
    | - "midtrans"   : Midtrans Snap (sandbox/production) + webhook terverifikasi.
    |
    | Ubah lewat .env: PAYMENT_DRIVER=midtrans
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'simulation'),

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Verifikasi Pembayaran
    |--------------------------------------------------------------------------
    |
    | Menentukan siapa yang menyelesaikan pesanan setelah pembeli menandai
    | "Saya Sudah Bayar" (dan/atau mengunggah bukti pembayaran):
    |
    | - "hybrid" (default) : bila bukti pembayaran diunggah, pesanan langsung
    |                        Selesai (terverifikasi otomatis dari bukti). Bila
    |                        tidak ada bukti, pesanan menunggu verifikasi admin.
    | - "manual"           : bukti hanya menjadi lampiran; admin selalu yang
    |                        memutuskan (paling aman, tanpa konfirmasi otomatis).
    | - "auto"             : setiap penandaan "sudah bayar" langsung Selesai,
    |                        dengan atau tanpa bukti.
    |
    | Ubah lewat .env: PAYMENT_VERIFICATION=manual
    |
    | Catatan: mode "hybrid"/"auto" menyerahkan keputusan kepada pembeli, jadi
    | hanya cocok untuk demo/simulasi. Untuk toko sungguhan gunakan "manual".
    |
    */

    'verification' => env('PAYMENT_VERIFICATION', 'hybrid'),

    'drivers' => [

        'simulation' => [
            'secret' => env('PAYMENT_SIMULATION_SECRET', env('APP_KEY')),
        ],

        'midtrans' => [
            'server_key'    => env('MIDTRANS_SERVER_KEY'),
            'client_key'    => env('MIDTRANS_CLIENT_KEY'),
            'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
            'is_3ds'        => env('MIDTRANS_IS_3DS', true),
            'base_url'      => env('MIDTRANS_IS_PRODUCTION', false)
                ? 'https://app.midtrans.com'
                : 'https://app.sandbox.midtrans.com',
            'timeout'       => env('MIDTRANS_TIMEOUT', 15),
        ],

    ],

];
