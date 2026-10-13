<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Notifications\TransactionStatusChanged;
use App\Services\MarketplaceOrderService;
use App\Services\TransactionService;
use App\Support\OrderStatus;
use App\Support\PaymentWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Batas waktu pembayaran (kedaluwarsa pesanan).
 *
 * Prinsip yang diuji:
 *   1. Pesanan baru selalu punya batas waktu yang benar-benar berlaku.
 *   2. Lewat batas waktu -> dibatalkan otomatis saat diakses (lazy expiry),
 *      stok kembali, dan jejaknya jujur (alasan + sumber keputusan).
 *   3. Pesanan yang pembelinya SUDAH menandai "sudah bayar" tidak pernah
 *      dibatalkan oleh jam — itu keputusan admin.
 *   4. Baris lama tanpa `expires_at` tidak pernah ikut kedaluwarsa.
 */
class PaymentExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    /* ---------------------------------------------------------------
     |  Bantuan
     * -------------------------------------------------------------- */

    private function admin(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    /**
     * Transaksi top up pending; `expires_at` bisa digeser ke masa lalu.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeTransaction(User $user, Product $product, array $overrides = []): Transaction
    {
        $stockBefore = $product->stock;

        $transaction = Transaction::create(array_merge([
            'transaction_code' => 'TXN-EXP-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => OrderStatus::PENDING,
            'expires_at'       => now()->addMinutes(PaymentWindow::minutes()),
        ], $overrides));

        // Pesanan pending memegang stok — tiru efek checkout.
        $product->update(['stock' => $stockBefore - 1]);

        return $transaction;
    }

    /**
     * Pesanan marketplace pending.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeOrder(User $buyer, MarketplaceProduct $product, array $overrides = []): MarketplaceOrder
    {
        $stockBefore = $product->stock;

        $order = MarketplaceOrder::create(array_merge([
            'order_code'     => 'MKT-EXP-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => OrderStatus::PENDING,
            'expires_at'     => now()->addMinutes(PaymentWindow::minutes()),
        ], $overrides));

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        $product->update(['stock' => $stockBefore - 1]);

        return $order;
    }

    /* ---------------------------------------------------------------
     |  1. Batas waktu dibuat untuk pesanan baru
     * -------------------------------------------------------------- */

    /** Checkout top up menyimpan batas waktu pembayaran. */
    public function test_topup_checkout_sets_payment_deadline()
    {
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)->post(route('topup.checkout.store'), [
            'game_slug'    => $game->slug,
            'user_game_id' => '12345678',
            'product_id'   => $product->id,
        ]);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::firstOrFail();

        $this->assertNotNull($transaction->expires_at);
        $this->assertTrue($transaction->hasPaymentDeadline());
        $this->assertEqualsWithDelta(
            PaymentWindow::minutes() * 60,
            $transaction->expires_at->getTimestamp() - now()->getTimestamp(),
            5
        );
    }

    /** Checkout marketplace menyimpan batas waktu pembayaran. */
    public function test_marketplace_checkout_sets_payment_deadline()
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $product = MarketplaceProduct::where('stock', '>', 0)->firstOrFail();

        $this->actingAs($buyer)->post(route('marketplace.add-to-cart', $product->slug), [
            'quantity' => 1,
        ]);

        $this->actingAs($buyer)->post(route('marketplace.checkout.process'), [
            'payment_method' => 'QRIS',
        ]);

        $order = MarketplaceOrder::firstOrFail();

        $this->assertNotNull($order->expires_at);
        $this->assertTrue($order->hasPaymentDeadline());
    }

    /* ---------------------------------------------------------------
     |  2. Lazy expiry: dibatalkan saat diakses
     * -------------------------------------------------------------- */

    /** Halaman sukses membatalkan pesanan yang sudah lewat batas waktu. */
    public function test_expired_transaction_is_cancelled_when_success_page_opens()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $stockBefore = $product->stock;

        $transaction = $this->makeTransaction($buyer, $product, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Waktu Pembayaran Habis');

        $transaction->refresh();

        $this->assertSame(OrderStatus::CANCELLED, $transaction->status);
        $this->assertTrue($transaction->wasCancelledByExpiry());
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    /** Pesanan marketplace yang kedaluwarsa mengembalikan stok ke penjual. */
    public function test_expired_marketplace_order_is_cancelled_and_stock_restored()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->firstOrFail();
        $stockBefore = $product->stock;

        $order = $this->makeOrder($buyer, $product, ['expires_at' => now()->subMinute()]);

        $this->actingAs($buyer)
            ->get(route('marketplace.success', $order->order_code))
            ->assertOk()
            ->assertSee('Waktu Pembayaran Habis');

        $order->refresh();

        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertTrue($order->wasCancelledByExpiry());
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    /** Portal pembayaran menolak menampilkan instruksi untuk pesanan kedaluwarsa. */
    public function test_portal_shows_closed_state_for_expired_transaction()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Pesanan Tidak Dapat Dibayar')
            ->assertSee('melewati batas waktu pembayaran')
            ->assertDontSee('Saya Sudah Bayar');

        $this->assertSame(OrderStatus::CANCELLED, $transaction->fresh()->status);
    }

    /** Invoice menampilkan status sebenarnya setelah batas waktu lewat. */
    public function test_invoice_reflects_cancellation_after_expiry()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($buyer)
            ->get(route('invoice.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Dibatalkan');

        $this->assertSame(OrderStatus::CANCELLED, $transaction->fresh()->status);
    }

    /** Daftar admin menyapu pesanan yang sudah lewat batas waktu. */
    public function test_admin_index_sweeps_overdue_orders()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.transactions.index'))
            ->assertOk();

        $this->assertSame(OrderStatus::CANCELLED, $transaction->fresh()->status);
    }

    /** Riwayat pembeli hanya menyapu pesanannya sendiri. */
    public function test_buyer_history_sweeps_only_own_overdue_orders()
    {
        $buyer = $this->buyer();
        $other = $this->buyer();
        $product = Product::first();

        $mine = $this->makeTransaction($buyer, $product, ['expires_at' => now()->subMinute()]);
        $theirs = $this->makeTransaction($other, $product, ['expires_at' => now()->subMinute()]);

        $this->actingAs($buyer)->get(route('transactions.index'))->assertOk();

        $this->assertSame(OrderStatus::CANCELLED, $mine->fresh()->status);
        $this->assertSame(OrderStatus::PENDING, $theirs->fresh()->status);
    }

    /* ---------------------------------------------------------------
     |  3. Kejujuran: siapa yang memutuskan
     * -------------------------------------------------------------- */

    /** Pesanan yang sudah ditandai "sudah bayar" tidak dibatalkan oleh jam. */
    public function test_order_already_claimed_is_not_expired()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product, [
            'expires_at'       => now()->subHours(2),
            'payment_claimed_at' => now()->subHours(3),
        ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk();

        $transaction->refresh();

        $this->assertSame(OrderStatus::PENDING, $transaction->status);
        $this->assertFalse($transaction->wasCancelledByExpiry());
        $this->assertFalse($transaction->paymentWindowExpired());
    }

    /** Klaim setelah batas waktu ditolak dan pesanannya ditandai kedaluwarsa. */
    public function test_claim_after_deadline_is_rejected()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product, [
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code))
            ->assertRedirect(route('topup.success', $transaction->transaction_code))
            ->assertSessionHas('error');

        $transaction->refresh();

        $this->assertSame(OrderStatus::CANCELLED, $transaction->status);
        $this->assertNull($transaction->payment_claimed_at);
        $this->assertTrue($transaction->wasCancelledByExpiry());
    }

    /** Pembatalan otomatis mencatat alasan + sumber keputusan, bukan menyamar admin. */
    public function test_expiry_records_reason_and_source()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinute(),
        ]);

        app(TransactionService::class)->expireIfDue($transaction);

        $transaction->refresh();

        $this->assertSame(OrderStatus::CANCELLED, $transaction->status);
        $this->assertSame(OrderStatus::SOURCE_EXPIRY, $transaction->confirmation_source);
        $this->assertNotNull($transaction->failure_reason);
        $this->assertStringContainsString('batas waktu', $transaction->failure_reason);
        $this->assertNull($transaction->confirmed_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'transaction.expired']);
    }

    /** Pembeli diberi tahu bahwa pesanannya dibatalkan otomatis. */
    public function test_expiry_notifies_the_buyer()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinute(),
        ]);

        app(TransactionService::class)->expireIfDue($transaction);

        Notification::assertSentTo($buyer, TransactionStatusChanged::class);
    }

    /** Pesanan marketplace yang kedaluwarsa juga memberi tahu pembeli. */
    public function test_marketplace_expiry_notifies_the_buyer()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->firstOrFail();

        $order = $this->makeOrder($buyer, $product, ['expires_at' => now()->subMinute()]);

        app(MarketplaceOrderService::class)->expireIfDue($order);

        Notification::assertSentTo($buyer, OrderStatusChanged::class);
    }

    /* ---------------------------------------------------------------
     |  4. Baris lama & idempotensi
     * -------------------------------------------------------------- */

    /** Tanpa `expires_at` (baris lama) pesanan tidak pernah kedaluwarsa. */
    public function test_transaction_without_deadline_never_expires()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => null,
        ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk();

        $this->assertSame(OrderStatus::PENDING, $transaction->fresh()->status);
        $this->assertFalse($transaction->paymentWindowExpired());
    }

    /** Kedaluwarsa bersifat idempotent: dipanggil dua kali hanya sekali membatalkan. */
    public function test_expiry_is_idempotent()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => now()->subMinute(),
        ]);

        $service = app(TransactionService::class);

        $this->assertTrue($service->expireIfDue($transaction));
        $this->assertFalse($service->expireIfDue($transaction->fresh()));
        $this->assertFalse($service->expireIfDue($transaction->fresh()));
    }

    /** Pembatalan sebelum batas waktu tidak dicap sebagai kedaluwarsa. */
    public function test_manual_cancel_before_deadline_is_not_marked_expired()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.cancel', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame(OrderStatus::CANCELLED, $transaction->status);
        $this->assertFalse($transaction->wasCancelledByExpiry());
        $this->assertNull($transaction->confirmation_source);
        $this->assertNull($transaction->failure_reason);
    }

    /* ---------------------------------------------------------------
     |  5. Buka ulang oleh admin
     * -------------------------------------------------------------- */

    /** Buka ulang memberi jendela pembayaran yang baru, bukan yang sudah basi. */
    public function test_reopening_an_expired_order_gives_a_fresh_window()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product, [
            'expires_at' => now()->subMinute(),
        ]);

        app(TransactionService::class)->expireIfDue($transaction);

        $this->actingAs($this->admin())
            ->post(route('admin.transactions.reopen', $transaction->id))
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame(OrderStatus::PENDING, $transaction->status);
        $this->assertNotNull($transaction->expires_at);
        $this->assertTrue($transaction->expires_at->isFuture());
        $this->assertFalse($transaction->wasCancelledByExpiry());
        $this->assertNull($transaction->confirmation_source);
        $this->assertNull($transaction->failure_reason);
    }

    /* ---------------------------------------------------------------
     |  6. Hitungan mundur di UI
     * -------------------------------------------------------------- */

    /** Halaman sukses menampilkan hitungan mundur selama masih bisa dibayar. */
    public function test_success_page_shows_countdown_while_pending()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Selesaikan pembayaran dalam')
            ->assertSee('data-countdown="' . $transaction->expires_at->toIso8601String() . '"', false);
    }

    /** Hitungan mundur disembunyikan begitu pembeli menandai sudah bayar. */
    public function test_countdown_hidden_when_payment_already_claimed()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'payment_claimed_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertDontSee('Selesaikan pembayaran dalam');
    }

    /** Portal menampilkan hitungan mundur untuk pesanan yang masih bisa dibayar. */
    public function test_portal_shows_countdown_while_payable()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Selesaikan pembayaran dalam');
    }

    /** Pesanan tanpa batas waktu tidak menampilkan hitungan mundur sama sekali. */
    public function test_countdown_absent_without_deadline()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'expires_at' => null,
        ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertDontSee('data-countdown', false);
    }

    /* ---------------------------------------------------------------
     |  7. Konfigurasi jendela pembayaran
     * -------------------------------------------------------------- */

    /** Nilai `.env` yang tidak masuk akal dijepit, bukan dipercaya apa adanya. */
    public function test_payment_window_minutes_are_clamped()
    {
        config(['payment.window' => 0]);
        $this->assertSame(PaymentWindow::MIN_MINUTES, PaymentWindow::minutes());

        config(['payment.window' => -30]);
        $this->assertSame(PaymentWindow::MIN_MINUTES, PaymentWindow::minutes());

        config(['payment.window' => 999999]);
        $this->assertSame(PaymentWindow::MAX_MINUTES, PaymentWindow::minutes());

        config(['payment.window' => 180]);
        $this->assertSame(180, PaymentWindow::minutes());
        $this->assertSame('3 jam', PaymentWindow::label());
    }

    /** Alasan kedaluwarsa menyebut durasi nyata supaya aturannya jelas. */
    public function test_expired_reason_mentions_the_window()
    {
        config(['payment.window' => 180]);

        $this->assertSame(
            'Pembayaran tidak diterima dalam batas waktu 3 jam.',
            PaymentWindow::expiredReason()
        );
    }
}
