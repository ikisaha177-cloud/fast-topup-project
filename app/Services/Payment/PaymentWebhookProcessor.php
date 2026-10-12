<?php

namespace App\Services\Payment;

use App\Models\MarketplaceOrder;
use App\Models\PaymentWebhookEvent;
use App\Models\Transaction;
use App\Services\MarketplaceOrderService;
use App\Services\TransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Memproses webhook pembayaran secara idempotent.
 *
 * Alur:
 *  1. "Klaim" event_id dengan menyisipkan baris ke payment_webhook_events.
 *     Kolom event_id unik, jadi bila gateway mengirim ulang (atau dua
 *     request datang bersamaan) hanya satu yang berhasil; sisanya dianggap
 *     duplikat dan tidak diproses lagi.
 *  2. Cari transaksi/pesanan berdasarkan referensi.
 *  3. Serahkan perubahan status ke service domain (stok + notifikasi konsisten).
 *
 * Klaim dan perubahan status dibungkus SATU transaksi database: kalau
 * perubahan status gagal, baris klaim ikut di-rollback sehingga gateway
 * masih bisa mengirim ulang dan event diproses (bukan hilang).
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
        try {
            return DB::transaction(function () use ($event, $provider) {
                // 1) Klaim event (gagal dengan unique violation bila duplikat).
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
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return [
                    'processed' => false,
                    'reason'    => 'duplicate',
                    'reference' => $event->reference,
                ];
            }

            throw $e;
        }
    }

    /**
     * Deteksi pelanggaran unique constraint (lintas driver database).
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) $e->getCode();
        $driverCode = $e->errorInfo[1] ?? null;

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, [19, 1062, 2601, 2627], true)
            || stripos($e->getMessage(), 'unique') !== false
            || stripos($e->getMessage(), 'duplicate') !== false;
    }
}
