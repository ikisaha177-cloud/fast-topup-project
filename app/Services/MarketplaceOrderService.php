<?php

namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;

/**
 * Perpindahan status pesanan marketplace + pengembalian stock + notifikasi.
 */
class MarketplaceOrderService
{
    /**
     * Ubah status pesanan marketplace secara aman.
     *
     * - pending -> success  : pembayaran diterima.
     * - pending -> cancelled/failed: stock tiap item dikembalikan ke penjual.
     *
     * @param  \App\Models\MarketplaceOrder  $order
     * @param  string                        $status
     * @param  bool                          $notify
     * @return bool  true bila status berubah
     */
    public function transitionTo(MarketplaceOrder $order, string $status, bool $notify = true)
    {
        if (! in_array($status, ['pending', 'success', 'cancelled', 'failed'], true)) {
            return false;
        }

        if ($order->status === $status) {
            return false;
        }

        $shouldRestoreStock = in_array($status, ['cancelled', 'failed'], true)
            && $order->status === 'pending';

        DB::transaction(function () use ($order, $status, $shouldRestoreStock) {
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

            $order->update(['status' => $status]);
        });

        if ($notify) {
            $this->notify($order->fresh(), $status);
        }

        return true;
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
            'success'   => 'Pembayaran diterima, pesananmu diproses penjual',
            'cancelled' => 'Pesanan dibatalkan',
            'failed'    => 'Pembayaran gagal',
            'pending'   => 'Pesanan menunggu pembayaran',
        ][$status] ?? 'Status pesanan diperbarui';

        $buyer->notify(new OrderStatusChanged($order, $headline));
    }
}
