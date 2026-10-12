<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes (stateless)
|--------------------------------------------------------------------------
|
| Dipanggil server-ke-server oleh payment gateway, sehingga TIDAK memakai
| grup middleware "web" (tanpa session & tanpa CSRF). Keamanan bergantung
| pada verifikasi signature di dalam driver gateway masing-masing.
|
*/

Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle'])
    ->name('payment.webhook');
