<?php

namespace App\Providers;

use App\Services\MarketplaceOrderService;
use App\Services\Payment\PaymentGatewayManager;
use App\Services\TransactionService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentGatewayManager::class, function () {
            return new PaymentGatewayManager();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /*
         * Produksi selalu diakses lewat HTTPS. Memaksa skema di sini membuat
         * seluruh URL yang dihasilkan (aset, tautan, email) memakai https://
         * walau aplikasi berada di belakang proxy yang meneruskan HTTP.
         */
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
