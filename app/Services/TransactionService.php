<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use App\Notifications\TransactionStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * Semua perpindahan status transaksi top up lewat sini supaya
 * aturan stok & notifikasi konsisten (single source of truth).
 */
class TransactionService
{
    /**
     * Ubah status transaksi secara aman.
     *
     * - pending -> success  : pembayaran diterima.
     * - pending -> cancelled: pesanan batal, stock dikembalikan.
     * - status lain tidak berubah (idempotent).
     *
     * @param  \App\Models\Transaction  $transaction
     * @param  string                   $status
     * @param  bool                     $notify
     * @return bool  true bila status berubah
     */
    public function transitionTo(Transaction $transaction, string $status, bool $notify = true)
    {
        if (! in_array($status, ['pending', 'success', 'cancelled', 'failed'], true)) {
            return false;
        }

        if ($transaction->status === $status) {
            return false;
        }

        // Stock hanya dikembalikan saat transaksi berhasil sebelumnya dibatalkan/digagal.
        $shouldRestoreStock = in_array($status, ['cancelled', 'failed'], true)
            && $transaction->status === 'pending';

        DB::transaction(function () use ($transaction, $status, $shouldRestoreStock) {
            if ($shouldRestoreStock) {
                $product = Product::whereKey($transaction->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->increment('stock');
                }
            }

            $transaction->update(['status' => $status]);
        });

        if ($notify) {
            $this->notify($transaction->fresh(), $status);
        }

        return true;
    }

    /**
     * Kirim email pemberitahuan status ke pemilik transaksi.
     */
    public function notify(Transaction $transaction, string $status)
    {
        $user = $transaction->user;

        if (! $user) {
            return;
        }

        $headline = [
            'success'   => 'Pembayaran diterima, pesananmu diproses',
            'cancelled' => 'Pesanan dibatalkan',
            'failed'    => 'Pembayaran gagal',
            'pending'   => 'Pesanan menunggu pembayaran',
        ][$status] ?? 'Status pesanan diperbarui';

        $user->notify(new TransactionStatusChanged($transaction, $headline));
    }
}
