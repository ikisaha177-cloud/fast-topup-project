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
 *   failed/cancelled --admin buka ulang--> pending (stok dikunci ulang)
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

        $reopening = $status === OrderStatus::PENDING && OrderStatus::isReopenable($transaction->status);

        if (! $reopening && ! OrderStatus::canTransition($transaction->status, $status)) {
            return false;
        }

        // Stok dikembalikan saat pesanan pending dibatalkan/digagal,
        // dan dikunci ULANG saat keputusan gagal/batal dibuka kembali.
        $shouldRestoreStock = in_array($status, [OrderStatus::FAILED, OrderStatus::CANCELLED], true)
            && $transaction->status === OrderStatus::PENDING;

        $shouldReserveStock = $reopening;

        // Simpan path bukti lama untuk dibersihkan bila pesanan dibuka ulang
        // (atributnya di-null-kan, jadi berkasnya tidak boleh tertinggal).
        $orphanProof = $reopening ? $transaction->payment_proof : null;

        // Dipakai closure agar bisa membatalkan tanpa melempar exception:
        // stok tidak boleh pernah jadi negatif saat dikunci ulang.
        $ok = true;

        DB::transaction(function () use ($transaction, $status, $shouldRestoreStock, $shouldReserveStock, $context, &$ok) {
            $product = Product::whereKey($transaction->product_id)->lockForUpdate()->first();

            if ($product && $shouldRestoreStock) {
                $product->increment('stock');
            }

            if ($product && $shouldReserveStock) {
                if ((int) $product->stock < 1) {
                    $ok = false; // stok habis terjual orang lain — biarkan tetap gagal

                    return;
                }

                $product->decrement('stock');
            }

            $transaction->update($this->attributesFor($transaction, $status, $context));
        });

        if (! $ok) {
            return false;
        }

        // Hapus berkas bukti yang sudah tidak dirujuk lagi.
        if ($orphanProof) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($orphanProof);
        }

        if ($notify) {
            $this->notify($transaction->fresh(), $status, $context['source'] ?? null);
        }

        return true;
    }

    /**
     * Tandai pembeli sudah membayar (masih menunggu verifikasi admin).
     *
     * @param  string|null  $proofPath  path bukti pembayaran (opsional)
     * @return bool  true bila penandaan baru saja dicatat
     */
    public function claimPayment(Transaction $transaction, $proofPath = null)
    {
        if (! $transaction->isPending()) {
            return false;
        }

        // Sudah ditandai sebelumnya: hanya izinkan melengkapi bukti yang belum ada.
        if ($transaction->isPaymentClaimed()) {
            if ($proofPath && ! $transaction->hasPaymentProof()) {
                $transaction->update(['payment_proof' => $proofPath]);

                return true;
            }

            return false;
        }

        $attributes = ['payment_claimed_at' => now()];

        if ($proofPath) {
            $attributes['payment_proof'] = $proofPath;
        }

        $transaction->update($attributes);

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

        if ($status === OrderStatus::PENDING) {
            // Buka ulang keputusan yang salah: bersihkan seluruh jejak verifikasi
            // supaya pesanan kembali benar-benar seperti baru.
            $attributes['paid_at']             = null;
            $attributes['payment_claimed_at']  = null;
            $attributes['payment_proof']       = null;
            $attributes['confirmed_at']        = null;
            $attributes['confirmed_by']        = null;
            $attributes['confirmation_source'] = null;
            $attributes['failure_reason']      = null;

            return $attributes;
        }

        if ($status === OrderStatus::SUCCESS) {
            $source = $this->normalizeSource($context);

            $attributes['paid_at']             = $transaction->paid_at ?: now();
            $attributes['payment_claimed_at']  = $transaction->payment_claimed_at ?: now();
            $attributes['confirmed_at']        = $transaction->confirmed_at ?: now();
            $attributes['confirmation_source'] = $source;
            $attributes['failure_reason']      = null;

            // Hanya konfirmasi manual admin yang mengisi confirmed_by.
            $attributes['confirmed_by'] = $source === OrderStatus::SOURCE_ADMIN
                ? ($context['actor_id'] ?? $transaction->confirmed_by)
                : null;
        }

        if ($status === OrderStatus::FAILED) {
            $source = $this->normalizeSource($context);

            $attributes['confirmed_at']        = $transaction->confirmed_at ?: now();
            $attributes['confirmation_source'] = $source;
            $attributes['confirmed_by']        = $source === OrderStatus::SOURCE_ADMIN
                ? ($context['actor_id'] ?? $transaction->confirmed_by)
                : null;

            if (! empty($context['reason'])) {
                $attributes['failure_reason'] = $context['reason'];
            }
        }

        return $attributes;
    }

    /**
     * Sumber konfirmasi: admin (manual) atau gateway (webhook otomatis).
     */
    private function normalizeSource(array $context)
    {
        return ($context['source'] ?? null) === OrderStatus::SOURCE_GATEWAY
            ? OrderStatus::SOURCE_GATEWAY
            : OrderStatus::SOURCE_ADMIN;
    }

    /**
     * Kirim email pemberitahuan status ke pemilik transaksi.
     *
     * @param  string|null  $source  admin|gateway — memengaruhi kata-kata email.
     */
    public function notify(Transaction $transaction, string $status, $source = null)
    {
        $user = $transaction->user;

        if (! $user) {
            return;
        }

        $byGateway = $source === OrderStatus::SOURCE_GATEWAY;

        $headline = [
            OrderStatus::SUCCESS   => $byGateway
                ? 'Pembayaran diterima, pesananmu selesai'
                : 'Pesananmu sudah dikonfirmasi admin dan selesai',
            OrderStatus::CANCELLED => 'Pesanan dibatalkan',
            OrderStatus::FAILED    => $byGateway
                ? 'Pembayaran gagal diproses'
                : 'Pesanan gagal dikonfirmasi admin',
            OrderStatus::PENDING   => 'Pesanan dibuka kembali, menunggu konfirmasi admin',
        ][$status] ?? 'Status pesanan diperbarui';

        $user->notify(new TransactionStatusChanged($transaction, $headline));
    }
}
