<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use App\Notifications\TransactionStatusChanged;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Semua perpindahan status transaksi top up lewat sini supaya
 * aturan stok, jejak verifikasi, & notifikasi konsisten
 * (single source of truth).
 *
 * Alur:
 *   pending --admin konfirmasi--> success   (pesanan selesai)
 *   pending --admin tolak-------> failed    (stok dikembalikan)
 *   pending --pembeli batal-----> cancelled (stok dikembalikan)
 */
class TransactionService
{
    /**
     * Ubah status transaksi secara aman.
     *
     * Hanya transisi yang sah (lihat OrderStatus) yang diproses; selain itu
     * dianggap tidak ada perubahan (idempotent).
     *
     * @param  \App\Models\Transaction  $transaction
     * @param  string                   $status
     * @param  bool                     $notify
     * @param  array<string, mixed>     $context  actor_id (admin), reason (alasan gagal)
     * @return bool  true bila status berubah
     */
    public function transitionTo(Transaction $transaction, string $status, bool $notify = true, array $context = [])
    {
        if (! in_array($status, OrderStatus::all(), true)) {
            return false;
        }

        if ($transaction->status === $status) {
            return false;
        }

        if (! OrderStatus::canTransition($transaction->status, $status)) {
            return false;
        }

        // Stock hanya dikembalikan saat pesanan yang masih pending dibatalkan/digagal.
        $shouldRestoreStock = in_array($status, [OrderStatus::FAILED, OrderStatus::CANCELLED], true)
            && $transaction->status === OrderStatus::PENDING;

        DB::transaction(function () use ($transaction, $status, $shouldRestoreStock, $context) {
            if ($shouldRestoreStock) {
                $product = Product::whereKey($transaction->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->increment('stock');
                }
            }

            $transaction->update($this->attributesFor($transaction, $status, $context));
        });

        if ($notify) {
            $this->notify($transaction->fresh(), $status);
        }

        return true;
    }

    /**
     * Tandai pembeli sudah membayar (masih menunggu verifikasi admin).
     *
     * @return bool  true bila penandaan baru saja dicatat
     */
    public function claimPayment(Transaction $transaction)
    {
        if (! $transaction->isPending() || $transaction->isPaymentClaimed()) {
            return false;
        }

        $transaction->update(['payment_claimed_at' => now()]);

        return true;
    }

    /**
     * Susun atribut yang ikut tersimpan saat status berubah.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function attributesFor(Transaction $transaction, string $status, array $context)
    {
        $attributes = ['status' => $status];

        if ($status === OrderStatus::SUCCESS) {
            $attributes['paid_at']      = $transaction->paid_at ?: now();
            $attributes['confirmed_at'] = $transaction->confirmed_at ?: now();
            $attributes['confirmed_by'] = $context['actor_id'] ?? $transaction->confirmed_by;
            $attributes['failure_reason'] = null;
        }

        if ($status === OrderStatus::FAILED) {
            $attributes['confirmed_at'] = $transaction->confirmed_at ?: now();
            $attributes['confirmed_by'] = $context['actor_id'] ?? $transaction->confirmed_by;

            if (! empty($context['reason'])) {
                $attributes['failure_reason'] = $context['reason'];
            }
        }

        return $attributes;
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
            OrderStatus::SUCCESS   => 'Pesananmu sudah dikonfirmasi admin dan selesai',
            OrderStatus::CANCELLED => 'Pesanan dibatalkan',
            OrderStatus::FAILED    => 'Pesanan gagal dikonfirmasi admin',
            OrderStatus::PENDING   => 'Pesanan menunggu konfirmasi admin',
        ][$status] ?? 'Status pesanan diperbarui';

        $user->notify(new TransactionStatusChanged($transaction, $headline));
    }
}
