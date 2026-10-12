<?php

namespace App\Services\Payment;

use App\Models\MarketplaceOrder;
use App\Models\PaymentWebhookEvent;
use App\Models\Transaction;
use App\Services\MarketplaceOrderService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;

/**
 * Memproses webhook pembayaran secara idempotent.
 *
 * Alur:
 *  1. Catat event_id; kalau sudah pernah diproses -> tolak (return false).
 *  2. Cari transaksi/pesanan berdasarkan referensi.
 *  3. Serahkan perubahan status ke service domain (stok + notifikasi konsisten).
 *
 * Semua dalam satu transaksi database supaya "sudah diproses" dan
 * "status berubah" tidak pernah terpisah.
 */
class PaymentWebhookProcessor
{
    /** @var \App\Services\TransactionService */
    protected $transactions;

    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    public function __construct(TransactionService $transactions, MarketplaceOrderService $orders)
    {
        $this->transactions = $transactions;
        $this->orders       = $orders;
    }

    /**
     * Proses sebuah event webhook.
     *
     * @return array{processed: bool, reason: string, reference: string|null}
     */
    public function process(WebhookEvent $event, string $provider = 'unknown'): array
    {
        // 1) Idempotency: event_id harus unik.
        if (PaymentWebhookEvent::where('event_id', $event->eventId)->exists()) {
            return [
                'processed' => false,
                'reason'    => 'duplicate',
                'reference' => $event->reference,
            ];
        }

        return DB::transaction(function () use ($event, $provider) {
            PaymentWebhookEvent::create([
                'event_id'     => $event->eventId,
                'provider'     => $provider,
                'reference'    => $event->reference,
                'status'       => $event->status,
                'payload'      => $event->raw,
                'processed_at' => now(),
            ]);

            // 2) Cari target: transaksi top up dulu, lalu pesanan marketplace.
            $transaction = Transaction::where('transaction_code', $event->reference)->first();

            if ($transaction) {
                $changed = $this->transactions->transitionTo($transaction, $event->status);

                return [
                    'processed' => true,
                    'reason'    => $changed ? 'status-updated' : 'status-unchanged',
                    'reference' => $event->reference,
                ];
            }

            $order = MarketplaceOrder::where('order_code', $event->reference)->first();

            if ($order) {
                $changed = $this->orders->transitionTo($order, $event->status);

                return [
                    'processed' => true,
                    'reason'    => $changed ? 'status-updated' : 'status-unchanged',
                    'reference' => $event->reference,
                ];
            }

            return [
                'processed' => true,
                'reason'    => 'reference-not-found',
                'reference' => $event->reference,
            ];
        });
    }
}
