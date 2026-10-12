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
