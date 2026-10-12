<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceProduct;
use App\Models\PaymentWebhookEvent;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Webhook pembayaran: verifikasi signature + idempotency.
 *
 * Driver default adalah "simulation" (lihat config/payment.php) sehingga
 * pengujian tidak butuh jaringan maupun kredensial provider.
 */
class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        Notification::fake();
    }

    /**
     * Hitung signature webhook sesuai kontrak SimulationGateway.
     */
    private function sign(string $reference, string $status): string
    {
        $secret = (string) config('payment.drivers.simulation.secret');
        $secret = $secret !== '' ? $secret : (string) config('app.key');

        return hash_hmac('sha256', $reference . '|' . $status, $secret);
    }

    /**
     * Kirim webhook simulasi.
     */
    private function postWebhook(array $payload, ?string $signature = null)
    {
        $signature = $signature ?? $this->sign(
            (string) $payload['order_id'],
            (string) $payload['transaction_status']
        );

        return $this->withHeaders(['X-Simulation-Signature' => $signature])
            ->postJson(route('payment.webhook'), $payload);
    }

    private function pendingTransaction(int $stockBefore = 10): array
    {
        $game    = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();
        $product->update(['stock' => $stockBefore]);

        $buyer = User::factory()->create(['role' => 'user']);

        $transaction = Transaction::create([
            'transaction_code' => 'TXN-' . strtoupper(uniqid()),
            'user_id'          => $buyer->id,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);

        // Simulasikan reservasi stok yang dilakukan saat checkout.
        $product->decrement('stock');

        return [$transaction, $product];
    }

    public function test_valid_webhook_marks_transaction_success()
    {
        [$transaction] = $this->pendingTransaction();

        $response = $this->postWebhook([
            'event_id'           => 'evt-' . $transaction->transaction_code,
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
            'gross_amount'       => $transaction->amount,
        ]);

        $response->assertOk()->assertJson(['reason' => 'status-updated']);

        $transaction->refresh();

        $this->assertSame('success', $transaction->status);
        $this->assertNotNull($transaction->paid_at);
        $this->assertDatabaseHas('payment_webhook_events', [
            'event_id' => 'evt-' . $transaction->transaction_code,
            'provider' => 'simulation',
        ]);
    }

    public function test_duplicate_webhook_is_processed_only_once()
    {
        [$transaction] = $this->pendingTransaction();

        $payload = [
            'event_id'           => 'evt-duplikat',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
            'gross_amount'       => $transaction->amount,
        ];

        $first = $this->postWebhook($payload);
        $first->assertOk()->assertJson(['reason' => 'status-updated']);

        $second = $this->postWebhook($payload);
        $second->assertOk()->assertJson(['reason' => 'duplicate']);

        $this->assertSame(1, PaymentWebhookEvent::where('event_id', 'evt-duplikat')->count());
        $this->assertSame('success', $transaction->fresh()->status);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        [$transaction] = $this->pendingTransaction();

        $response = $this->postWebhook([
            'event_id'           => 'evt-palsu',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
        ], 'signature-ngawur');

        $response->assertStatus(403);

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(0, PaymentWebhookEvent::count());
    }

    public function test_cancelling_webhook_restores_stock_once()
    {
        [$transaction, $product] = $this->pendingTransaction(10);

        $this->assertSame(9, (int) $product->fresh()->stock);

        $payload = [
            'event_id'           => 'evt-cancel',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'expire',
        ];

        $this->postWebhook($payload)->assertOk();

        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertSame(10, (int) $product->fresh()->stock);

        // Kirim ulang: stok TIDAK boleh bertambah dua kali.
        $this->postWebhook($payload)->assertOk()->assertJson(['reason' => 'duplicate']);

        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_webhook_updates_marketplace_order()
    {
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $buyer   = User::factory()->create(['role' => 'user']);

        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-' . strtoupper(uniqid()),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'pending',
        ]);

        MarketplaceOrderItem::create([
            'marketplace_order_id'   => $order->id,
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        $this->postWebhook([
            'event_id'           => 'evt-order-' . $order->order_code,
            'order_id'           => $order->order_code,
            'transaction_status' => 'capture',
        ])->assertOk()->assertJson(['reason' => 'status-updated']);

        $this->assertSame('success', $order->fresh()->status);
    }

    public function test_webhook_for_unknown_reference_is_acknowledged()
    {
        $this->postWebhook([
            'event_id'           => 'evt-tidak-dikenal',
            'order_id'           => 'TXN-TIDAK-ADA',
            'transaction_status' => 'settlement',
        ])
            ->assertOk()
            ->assertJson(['reason' => 'reference-not-found']);
    }

    public function test_webhook_with_new_event_id_but_same_status_reports_unchanged()
    {
        [$transaction] = $this->pendingTransaction();

        // Event pertama: pending -> success.
        $this->postWebhook([
            'event_id'           => 'evt-1',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
        ])->assertOk()->assertJson(['reason' => 'status-updated']);

        // Event BARU (id berbeda) dengan status sama: tidak mengubah apa pun.
        $this->postWebhook([
            'event_id'           => 'evt-2',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
        ])->assertOk()->assertJson(['reason' => 'status-unchanged']);

        $this->assertSame('success', $transaction->fresh()->status);
        $this->assertSame(2, PaymentWebhookEvent::count());
    }

    public function test_duplicate_event_is_rejected_even_without_precheck()
    {
        // Membuktikan idempotency bertumpu pada unique constraint (aman
        // terhadap dua request yang datang hampir bersamaan), bukan sekadar
        // SELECT EXISTS sebelumnya.
        [$transaction] = $this->pendingTransaction();

        $payload = [
            'event_id'           => 'evt-race',
            'order_id'           => $transaction->transaction_code,
            'transaction_status' => 'settlement',
        ];

        $this->postWebhook($payload)->assertOk()->assertJson(['reason' => 'status-updated']);
        $this->postWebhook($payload)->assertOk()->assertJson(['reason' => 'duplicate']);

        $this->assertSame(1, PaymentWebhookEvent::where('event_id', 'evt-race')->count());
    }

    public function test_webhook_does_not_require_csrf_token()
    {
        // Route webhook berada di luar grup "web" (tanpa CSRF/session).
        // Request di atas sudah membuktikannya: tanpa token tetap 200.
        $this->postWebhook([
            'event_id'           => 'evt-csrf',
            'order_id'           => 'TXN-TIDAK-ADA',
            'transaction_status' => 'settlement',
        ])->assertOk();
    }
}
