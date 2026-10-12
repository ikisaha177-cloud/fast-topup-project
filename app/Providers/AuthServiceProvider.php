<?php

namespace App\Providers;

use App\Models\MarketplaceOrder;
use App\Models\Transaction;
use App\Policies\MarketplaceOrderPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Transaction::class      => TransactionPolicy::class,
        MarketplaceOrder::class => MarketplaceOrderPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /*
         * Admin otomatis lolos semua pemeriksaan policy. Ini menjaga aturan
         * "admin boleh melihat & mengelola semuanya" tetap konsisten tanpa
         * menulis cek isAdmin berulang di setiap policy.
         *
         * $user bisa NULL (guest), jadi cek harus null-safe.
         */
        Gate::before(function ($user, $ability) {
            if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return true;
            }

            return null;
        });

        /*
         * Kemampuan khusus admin (dipakai di route/controller/Blade).
         */
        Gate::define('manage-catalog', function ($user = null) {
            return $user !== null && $user->isAdmin();
        });
    }
}
