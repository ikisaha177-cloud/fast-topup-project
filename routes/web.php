<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TopUpController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - FastTopup Store
|--------------------------------------------------------------------------
*/

/* ---------- Home ---------- */
Route::get('/', [HomeController::class, 'index'])->name('home');

/* ---------- Halaman statis: kebijakan & bantuan ---------- */
Route::get('/cara-order', [PageController::class, 'howToOrder'])->name('page.how-to-order');
Route::get('/kalkulator-winrate-mlbb', [PageController::class, 'winrate'])->name('page.winrate');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('page.terms');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('page.privacy');

/* ---------- Sitemap (untuk mesin pencari) ---------- */
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

/* ---------- Health check (monitoring uptime) ---------- */
Route::get('/up', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
})->name('health');

/* ---------- Login & Register (hanya untuk yang belum login) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');

    /* Lupa password */
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/* ---------- Verifikasi email (butuh login) ---------- */
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.resend');
});

/* ---------- Top Up Game (khusus pembeli, admin diblokir) ---------- */
Route::middleware('not-admin')->group(function () {
    Route::get('/top-up', [TopUpController::class, 'index'])->name('topup.index');
    Route::get('/top-up/checkout', [TopUpController::class, 'checkout'])->name('topup.checkout');
    Route::post('/top-up/checkout', [TopUpController::class, 'storeCheckout'])->middleware('throttle:checkout')->name('topup.checkout.store');
    Route::post('/top-up/checkout/promo', [TopUpController::class, 'applyPromo'])->middleware('throttle:promo')->name('topup.promo.apply');
    Route::post('/top-up/checkout/pay', [TopUpController::class, 'pay'])->middleware('throttle:payments')->name('topup.pay');
    Route::get('/top-up/success/{code}', [TopUpController::class, 'success'])->name('topup.success');
    Route::post('/top-up/payment/{code}/claim', [TopUpController::class, 'claimPayment'])->middleware('throttle:payments')->name('topup.payment.claim');
    Route::post('/top-up/payment/{code}/cancel', [TopUpController::class, 'cancelPayment'])->middleware('throttle:payments')->name('topup.payment.cancel');
    Route::get('/top-up/{slug}', [TopUpController::class, 'show'])->name('topup.show');
});

/* ---------- Marketplace ---------- */
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/checkout', [MarketplaceController::class, 'checkout'])
    ->middleware(['auth', 'not-admin'])->name('marketplace.checkout');
Route::post('/marketplace/checkout', [MarketplaceController::class, 'processCheckout'])
    ->middleware(['auth', 'not-admin', 'throttle:checkout'])->name('marketplace.checkout.process');
Route::post('/marketplace/checkout/promo', [MarketplaceController::class, 'applyPromo'])
    ->middleware(['auth', 'not-admin', 'throttle:promo'])->name('marketplace.promo.apply');
Route::get('/marketplace/success/{code}', [MarketplaceController::class, 'success'])
    ->middleware(['auth', 'not-admin'])->name('marketplace.success');
Route::post('/marketplace/payment/{code}/claim', [MarketplaceController::class, 'claimPayment'])
    ->middleware(['auth', 'not-admin', 'throttle:payments'])->name('marketplace.payment.claim');
Route::post('/marketplace/payment/{code}/cancel', [MarketplaceController::class, 'cancelPayment'])
    ->middleware(['auth', 'not-admin', 'throttle:payments'])->name('marketplace.payment.cancel');
Route::get('/marketplace/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');
Route::post('/marketplace/{slug}/add', [MarketplaceController::class, 'addToCart'])
    ->middleware(['auth', 'not-admin'])->name('marketplace.add-to-cart');

/* ---------- Ulasan produk (hanya pembeli dengan pesanan Selesai) ----------
   Otorisasi + bukti pembelian diperiksa di controller; akun admin diblokir
   karena admin tidak pernah membeli. */
Route::post('/orders/{code}/review', [ReviewController::class, 'store'])
    ->middleware(['auth', 'not-admin', 'throttle:reviews'])->name('reviews.store');

/* ---------- Invoice pembayaran (top up & marketplace) ----------
   Otorisasi lewat policy masing-masing model, jadi guest yang tahu kode
   transaksi top up tetap boleh membukanya. */
Route::get('/invoice/{code}', [InvoiceController::class, 'show'])->name('invoice.show');

/* ---------- Portal pembayaran (halaman terpisah, khas "hosted checkout") ----------
   Tempat pembeli diarahkan untuk membayar lewat QRIS / e-wallet / transfer bank,
   lalu menerima STRUK yang bisa di-screenshot dan diunggah sebagai bukti.
   Otorisasi tetap lewat policy model — sama seperti invoice — jadi guest yang
   tahu kode transaksi top up boleh membukanya; akun admin diblokir karena
   portal ini khusus sisi pembeli. */
Route::middleware('not-admin')->group(function () {
    Route::get('/payment/{code}', [PaymentPortalController::class, 'show'])->name('payment.show');
    Route::post('/payment/{code}/confirm', [PaymentPortalController::class, 'confirm'])->middleware('throttle:payments')->name('payment.confirm');
});

/* ---------- Keranjang / Cart (session, admin diblokir) ---------- */
Route::middleware('not-admin')->group(function () {
    Route::get('/cart', [MarketplaceController::class, 'cart'])->name('cart.index');
    Route::post('/cart/update', [MarketplaceController::class, 'updateCart'])->name('cart.update');
    Route::post('/cart/remove', [MarketplaceController::class, 'removeCart'])->name('cart.remove');
});

/* ---------- Halaman yang butuh login ---------- */
Route::middleware('auth')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{code}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{code}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/* ---------- Admin (butuh login + role admin) ---------- */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    /* Game */
    Route::get('/games', [Admin\GameController::class, 'index'])->name('games.index');
    Route::get('/games/create', [Admin\GameController::class, 'create'])->name('games.create');
    Route::post('/games', [Admin\GameController::class, 'store'])->name('games.store');
    Route::get('/games/{id}/edit', [Admin\GameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{id}', [Admin\GameController::class, 'update'])->name('games.update');
    Route::delete('/games/{id}', [Admin\GameController::class, 'destroy'])->name('games.destroy');
    Route::post('/games/{id}/toggle', [Admin\GameController::class, 'toggle'])->name('games.toggle');

    /* Produk Top Up */
    Route::get('/products', [Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [Admin\ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [Admin\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [Admin\ProductController::class, 'destroy'])->name('products.destroy');
    Route::post('/products/{id}/toggle', [Admin\ProductController::class, 'toggle'])->name('products.toggle');

    /* Produk Marketplace */
    Route::get('/marketplace', [Admin\MarketplaceProductController::class, 'index'])->name('marketplace.index');
    Route::get('/marketplace/{id}/edit', [Admin\MarketplaceProductController::class, 'edit'])->name('marketplace.edit');
    Route::put('/marketplace/{id}', [Admin\MarketplaceProductController::class, 'update'])->name('marketplace.update');
    Route::delete('/marketplace/{id}', [Admin\MarketplaceProductController::class, 'destroy'])->name('marketplace.destroy');
    Route::post('/marketplace/{id}/toggle', [Admin\MarketplaceProductController::class, 'toggle'])->name('marketplace.toggle');

    /* Kode Promo */
    Route::get('/promos', [Admin\PromoController::class, 'index'])->name('promos.index');
    Route::get('/promos/create', [Admin\PromoController::class, 'create'])->name('promos.create');
    Route::post('/promos', [Admin\PromoController::class, 'store'])->name('promos.store');
    Route::get('/promos/{id}/edit', [Admin\PromoController::class, 'edit'])->name('promos.edit');
    Route::put('/promos/{id}', [Admin\PromoController::class, 'update'])->name('promos.update');
    Route::delete('/promos/{id}', [Admin\PromoController::class, 'destroy'])->name('promos.destroy');
    Route::post('/promos/{id}/toggle', [Admin\PromoController::class, 'toggle'])->name('promos.toggle');

    /* Transaksi Top Up & Pesanan Marketplace */
    Route::get('/transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [Admin\TransactionController::class, 'show'])->name('transactions.show');
    Route::post('/transactions/{id}/status', [Admin\TransactionController::class, 'updateStatus'])->name('transactions.status');
    Route::post('/transactions/{id}/reopen', [Admin\TransactionController::class, 'reopen'])->name('transactions.reopen');
    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/status', [Admin\OrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{id}/reopen', [Admin\OrderController::class, 'reopen'])->name('orders.reopen');

    /* Laporan Harian / Bulanan / Tahunan */
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

    /* Log Aktivitas */
    Route::get('/activity', [Admin\ActivityLogController::class, 'index'])->name('activity.index');
});

