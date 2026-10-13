<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Notifications\OrderStatusChanged;
use App\Support\OrderStatus;
use App\Support\PaymentVerification;
use Illuminate\Support\Facades\DB;

/**
 * Perpindahan status pesanan marketplace + pengembalian stok + jejak
 * verifikasi + notifikasi.
 *
 * Alur:
 *   pending --admin konfirmasi--> success   (pesanan selesai)
 *   pending --admin tolak-------> failed    (stok tiap item dikembalikan)
 *   pending --pembeli batal-----> cancelled (stok tiap item dikembalikan)
 *   failed/cancelled --admin buka ulang--> pending (stok tiap item dikunci ulang)
 */
class MarketplaceOrderService
{
    /**
     * Ubah status pesanan marketplace secara aman.
     *
     * @param  \App\Models\MarketplaceOrder  $order
     * @param  string                        $status
     * @param  bool                          $notify
     * @param  array<string, mixed>          $context  actor_id (admin), reason (alasan gagal)
     * @return bool  true bila status berubah
     */
    public function transitionTo(MarketplaceOrder $order, string $status, bool $notify = true, array $context = [])
    {
        if (! in_array($status, OrderStatus::all(), true)) {
            return false;
        }

        if ($order->status === $status) {
            return false;
        }

        $reopening = $status === OrderStatus::PENDING && OrderStatus::isReopenable($order->status);

        if (! $reopening && ! OrderStatus::canTransition($order->status, $status)) {
            return false;
        }

        // Stok dikembalikan saat pesanan yang masih memegang stok
        // (pending/success) dibatalkan/digagal, dan dikunci ULANG saat
        // keputusan gagal/batal dibuka kembali.
        $shouldRestoreStock = in_array($status, [OrderStatus::FAILED, OrderStatus::CANCELLED], true)
            && in_array($order->status, [OrderStatus::PENDING, OrderStatus::SUCCESS], true);

        $shouldReserveStock = $reopening;

        // Simpan path bukti lama untuk dibersihkan bila pesanan dibuka ulang.
        $orphanProof = $reopening ? $order->payment_proof : null;

        // Dipakai closure agar bisa membatalkan tanpa exception: stok tidak
        // boleh pernah jadi negatif saat dikunci ulang.
        $ok = true;

        DB::transaction(function () use ($order, $status, $shouldRestoreStock, $shouldReserveStock, $context, &$ok) {
            // Pass 1: kunci semua produk & pastikan stok cukup sebelum menulis
            // apa pun. Keluar lebih dulu = tidak ada perubahan yang ter-commit.
            $products = [];

            foreach ($order->items as $item) {
                $product = MarketplaceProduct::whereKey($item->marketplace_product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $product) {
                    continue;
                }

                if ($shouldReserveStock && (int) $product->stock < (int) $item->quantity) {
                    $ok = false;

                    return;
                }

                $products[] = [$product, (int) $item->quantity];
            }

            // Pass 2: tulis perubahan stok.
            foreach ($products as [$product, $quantity]) {
                if ($shouldRestoreStock) {
                    $product->increment('stock', $quantity);
                }

                if ($shouldReserveStock) {
                    $product->decrement('stock', $quantity);
                }
            }

            $order->update($this->attributesFor($order, $status, $context));
        });

        if (! $ok) {
            return false;
        }

        // Hapus berkas bukti yang sudah tidak dirujuk lagi.
        if ($orphanProof) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($orphanProof);
        }

        if ($notify) {
            $this->notify($order->fresh(), $status, $context['source'] ?? null);
        }

        return true;
    }

    /**
     * Tandai pembeli sudah membayar.
     *
     * Secara bawaan pesanan menunggu verifikasi admin. Namun bila kebijakan
     * verifikasi ({@see PaymentVerification}) mengizinkan — mis. bukti
     * pembayaran diunggah pada mode "hybrid" — pesanan langsung Selesai
     * dengan jejak audit `confirmation_source = auto`.
     *
     * @param  string|null  $proofPath  path bukti pembayaran (opsional)
     * @return bool  true bila ada penandaan/kelengkapan bukti yang dicatat
     */
    public function claimPayment(MarketplaceOrder $order, $proofPath = null)
    {
        if (! $order->isPending()) {
            return false;
        }

        $recorded = false;

        // Sudah ditandai sebelumnya: hanya izinkan melengkapi bukti yang belum ada.
        if ($order->isPaymentClaimed()) {
            if ($proofPath && ! $order->hasPaymentProof()) {
                $order->update(['payment_proof' => $proofPath]);

                $recorded = true;
            }
        } else {
            $attributes = ['payment_claimed_at' => now()];

            if ($proofPath) {
                $attributes['payment_proof'] = $proofPath;
            }

            $order->update($attributes);

            $recorded = true;
        }

        if (! $recorded) {
            return false;
        }

        // Kebijakan verifikasi: mungkin langsung Selesai tanpa admin.
        if (PaymentVerification::autoConfirms($order->fresh()->hasPaymentProof())) {
            $this->transitionTo($order, OrderStatus::SUCCESS, true, [
                'source' => OrderStatus::SOURCE_AUTO,
            ]);
        }

        return true;
    }

    /**
     * Susun atribut yang ikut tersimpan saat status berubah.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function attributesFor(MarketplaceOrder $order, string $status, array $context)
    {
        $attributes = ['status' => $status];

        if ($status === OrderStatus::PENDING) {
            // Buka ulang keputusan yang salah: bersihkan seluruh jejak verifikasi.
            $attributes['paid_at']             = null;
            $attributes['payment_claimed_at']  = null;
            $attributes['payment_proof']       = null;
            $attributes['confirmed_at']        = null;
            $attributes['confirmed_by']        = null;
            $attributes['confirmation_source'] = null;
            $attributes['failure_reason']      = null;
            $attributes['revoked_at']          = null;
            $attributes['revoked_by']          = null;

            return $attributes;
        }

        if ($status === OrderStatus::SUCCESS) {
            $source = $this->normalizeSource($context);

            $attributes['paid_at']             = $order->paid_at ?: now();
            $attributes['payment_claimed_at']  = $order->payment_claimed_at ?: now();
            $attributes['confirmed_at']        = $order->confirmed_at ?: now();
            $attributes['confirmation_source'] = $source;
            $attributes['failure_reason']      = null;
            $attributes['revoked_at']          = null;
            $attributes['revoked_by']          = null;

            $attributes['confirmed_by'] = $source === OrderStatus::SOURCE_ADMIN
                ? ($context['actor_id'] ?? $order->confirmed_by)
                : null;
        }

        if ($status === OrderStatus::FAILED) {
            $source = $this->normalizeSource($context);

            // Pembatalan konfirmasi yang salah (success -> failed): pertahankan
            // jejak konfirmasi awal dan catat pembatalannya di kolom terpisah.
            $revoking = $order->status === OrderStatus::SUCCESS;

            if ($revoking) {
                $attributes['revoked_at'] = now();
                $attributes['revoked_by'] = $context['actor_id'] ?? null;
            } else {
                $attributes['confirmed_at']        = $order->confirmed_at ?: now();
                $attributes['confirmation_source'] = $source;
                $attributes['confirmed_by']        = $source === OrderStatus::SOURCE_ADMIN
                    ? ($context['actor_id'] ?? $order->confirmed_by)
                    : null;
            }

            if (! empty($context['reason'])) {
                $attributes['failure_reason'] = $context['reason'];
            }
        }

        return $attributes;
    }

    /**
     * Sumber konfirmasi: admin (manual), gateway (webhook otomatis), atau
     * auto (sistem, dari bukti/penandaan pembeli).
     */
    private function normalizeSource(array $context)
    {
        $source = $context['source'] ?? null;

        if (in_array($source, [OrderStatus::SOURCE_GATEWAY, OrderStatus::SOURCE_AUTO], true)) {
            return $source;
        }

        return OrderStatus::SOURCE_ADMIN;
    }

    /**
     * Kirim email pemberitahuan status ke pembeli.
     *
     * @param  string|null  $source  admin|gateway|auto — memengaruhi kata-kata email.
     */
    public function notify(MarketplaceOrder $order, string $status, $source = null)
    {
        $buyer = $order->buyer;

        if (! $buyer) {
            return;
        }

        $byGateway = $source === OrderStatus::SOURCE_GATEWAY;
        $byAuto    = $source === OrderStatus::SOURCE_AUTO;

        $headline = [
            OrderStatus::SUCCESS   => $byGateway
                ? 'Pembayaran diterima, pesananmu selesai'
                : ($byAuto
                    ? 'Pembayaran terverifikasi otomatis, pesananmu selesai'
                    : 'Pesananmu sudah dikonfirmasi admin dan selesai'),
            OrderStatus::CANCELLED => 'Pesanan dibatalkan',
            OrderStatus::FAILED    => $byGateway
                ? 'Pembayaran gagal diproses'
                : 'Pesanan gagal dikonfirmasi admin',
            OrderStatus::PENDING   => 'Pesanan dibuka kembali, menunggu konfirmasi admin',
        ][$status] ?? 'Status pesanan diperbarui';

        // Kegagalan pengiriman email (mis. SMTP tidak tersedia) tidak boleh
        // membatalkan perubahan status yang sudah tersimpan — cukup dicatat.
        try {
            $buyer->notify(new OrderStatusChanged($order, $headline));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
