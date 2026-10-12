<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Notifications\OrderStatusChanged;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Perpindahan status pesanan marketplace + pengembalian stock + jejak
 * verifikasi + notifikasi.
 *
 * Alur:
 *   pending --admin konfirmasi--> success   (pesanan selesai)
 *   pending --admin tolak-------> failed    (stok tiap item dikembalikan)
 *   pending --pembeli batal-----> cancelled (stok tiap item dikembalikan)
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

        if (! OrderStatus::canTransition($order->status, $status)) {
            return false;
        }

        $shouldRestoreStock = in_array($status, [OrderStatus::FAILED, OrderStatus::CANCELLED], true)
            && $order->status === OrderStatus::PENDING;

        DB::transaction(function () use ($order, $status, $shouldRestoreStock, $context) {
            if ($shouldRestoreStock) {
                foreach ($order->items as $item) {
                    $product = MarketplaceProduct::whereKey($item->marketplace_product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update($this->attributesFor($order, $status, $context));
        });

        if ($notify) {
            $this->notify($order->fresh(), $status);
        }

        return true;
    }

    /**
     * Tandai pembeli sudah membayar (masih menunggu verifikasi admin).
     *
     * @return bool  true bila penandaan baru saja dicatat
     */
    public function claimPayment(MarketplaceOrder $order)
    {
        if (! $order->isPending() || $order->isPaymentClaimed()) {
            return false;
        }

        $order->update(['payment_claimed_at' => now()]);

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

        if ($status === OrderStatus::SUCCESS) {
            $attributes['paid_at']        = $order->paid_at ?: now();
            $attributes['confirmed_at']   = $order->confirmed_at ?: now();
            $attributes['confirmed_by']   = $context['actor_id'] ?? $order->confirmed_by;
            $attributes['failure_reason'] = null;
        }

        if ($status === OrderStatus::FAILED) {
            $attributes['confirmed_at'] = $order->confirmed_at ?: now();
            $attributes['confirmed_by'] = $context['actor_id'] ?? $order->confirmed_by;

            if (! empty($context['reason'])) {
                $attributes['failure_reason'] = $context['reason'];
            }
        }

        return $attributes;
    }

    /**
     * Kirim email pemberitahuan status ke pembeli.
     */
    public function notify(MarketplaceOrder $order, string $status)
    {
        $buyer = $order->buyer;

        if (! $buyer) {
            return;
        }

        $headline = [
            OrderStatus::SUCCESS   => 'Pesananmu sudah dikonfirmasi admin dan selesai',
            OrderStatus::CANCELLED => 'Pesanan dibatalkan',
            OrderStatus::FAILED    => 'Pesanan gagal dikonfirmasi admin',
            OrderStatus::PENDING   => 'Pesanan menunggu konfirmasi admin',
        ][$status] ?? 'Status pesanan diperbarui';

        $buyer->notify(new OrderStatusChanged($order, $headline));
    }
}
