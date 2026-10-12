<?php

namespace App\Policies;

use App\Models\MarketplaceOrder;
use App\Models\User;

/**
 * Otorisasi untuk pesanan marketplace.
 * Pesanan selalu terikat akun pembeli, jadi guest tidak diizinkan.
 */
class MarketplaceOrderPolicy
{
    /**
     * Boleh melihat detail pesanan?
     */
    public function view(User $user, MarketplaceOrder $order)
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /**
     * Boleh mengubah status pesanan? (verifikasi manual = admin saja)
     */
    public function updateStatus(User $user, MarketplaceOrder $order)
    {
        return $user->isAdmin();
    }
}
