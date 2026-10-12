<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * Otorisasi untuk transaksi top up.
 *
 * Catatan: top up bisa dilakukan tanpa login (guest checkout), sehingga
 * transaksi dengan user_id NULL tetap boleh dilihat siapa saja yang tahu
 * kodenya — perilaku ini sengaja dipertahankan (first param nullable).
 */
class TransactionPolicy
{
    /**
     * Boleh melihat detail transaksi?
     */
    public function view(?User $user, Transaction $transaction)
    {
        if ($user && $user->isAdmin()) {
            return true;
        }

        // Guest checkout: transaksi tidak terikat akun.
        if ($transaction->user_id === null) {
            return true;
        }

        return $user !== null && $transaction->user_id === $user->id;
    }

    /**
     * Boleh mengubah status transaksi? (verifikasi manual = admin saja)
     */
    public function updateStatus(User $user, Transaction $transaction)
    {
        return $user->isAdmin();
    }

    /**
     * Boleh membuka kembali keputusan yang salah? (admin saja)
     */
    public function reopen(User $user, Transaction $transaction)
    {
        return $user->isAdmin();
    }
}
