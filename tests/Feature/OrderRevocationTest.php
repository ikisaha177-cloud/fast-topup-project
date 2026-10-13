<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Pembatalan konfirmasi oleh admin: salah konfirmasi (success) diperbaiki
 * menjadi gagal. Stok dikembalikan dan jejak audit dicatat terpisah sehingga
 * informasi konfirmasi awal tetap utuh.
 */
class OrderRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(User $user, Product $product, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'transaction_code' => 'TXN-REVOKE-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ], $overrides));
    }

    private function makeOrder(User $buyer, MarketplaceProduct $product): MarketplaceOrder
    {
        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-REVOKE-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'pending',
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        return $order;
    }

    /** Aturan transisi: success boleh turun ke failed, tetapi tidak sebaliknya. */
    public function test_success_may_transition_to_failed_only()
    {
        $this->assertSame([OrderStatus::FAILED], OrderStatus::allowedTransitions(OrderStatus::SUCCESS));
        $this->assertTrue(OrderStatus::canTransition(OrderStatus::SUCCESS, OrderStatus::FAILED));
        $this->assertFalse(OrderStatus::canTransition(OrderStatus::FAILED, OrderStatus::SUCCESS));
        $this->assertTrue(OrderStatus::canRevoke(OrderStatus::SUCCESS));
        $this->assertFalse(OrderStatus::canRevoke(OrderStatus::FAILED));
    }

    /** Batalkan konfirmasi transaksi: gagal, stok kembali, jejak audit tercatat. */
    public function test_admin_can_revoke_transaction_confirmation()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $stockAfterCheckout = $product->stock; // stok sudah dikurangi saat checkout

        $transaction = $this->makeTransaction($buyer, $product);

        // Konfirmasi pertama oleh admin.
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success']);
        $transaction->refresh();
        $this->assertSame('success', $transaction->status);
        $firstConfirmedBy = $transaction->confirmed_by;

        // Batalkan konfirmasi.
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), [
                'status'         => 'failed',
                'failure_reason' => 'Salah konfirmasi, dana belum masuk.',
            ])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('failed', $transaction->status);
        $this->assertSame('Salah konfirmasi, dana belum masuk.', $transaction->failure_reason);

        // Jejak konfirmasi awal tidak tertimpa; pembatalan dicatat terpisah.
        $this->assertSame($firstConfirmedBy, $transaction->confirmed_by);
        $this->assertTrue($transaction->wasRevoked());
        $this->assertNotNull($transaction->revoked_at);
        $this->assertSame($admin->id, $transaction->revoked_by);

        // Stok dikembalikan (naik 1 dari kondisi setelah checkout).
        $this->assertSame($stockAfterCheckout + 1, $product->fresh()->stock);
    }

    /** Batalkan konfirmasi pesanan marketplace: stok tiap item kembali. */
    public function test_admin_can_revoke_marketplace_confirmation()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $stockAfterCheckout = $product->stock;

        $order = $this->makeOrder($buyer, $product);

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->id), ['status' => 'success']);
        $this->assertSame('success', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->id), [
                'status'         => 'failed',
                'failure_reason' => 'Stok ternyata kosong.',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('failed', $order->status);
        $this->assertTrue($order->wasRevoked());
        $this->assertSame($admin->id, $order->revoked_by);
        $this->assertSame($stockAfterCheckout + 1, $product->fresh()->stock);
    }

    /** Konfirmasi gateway (webhook) juga boleh dibatalkan admin. */
    public function test_admin_can_revoke_gateway_confirmation()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product, [
            'status'              => 'success',
            'paid_at'             => now(),
            'confirmed_at'        => now(),
            'confirmation_source' => OrderStatus::SOURCE_GATEWAY,
            'confirmed_by'        => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'failed'])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('failed', $transaction->status);
        $this->assertTrue($transaction->wasRevoked());
        $this->assertNull($transaction->confirmed_by); // tetap tanpa admin pengonfirmasi
    }

    /** Halaman detail admin menampilkan tombol "Batalkan Konfirmasi" saat success. */
    public function test_admin_detail_shows_revoke_action_for_success()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success']);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $transaction->id))
            ->assertOk()
            ->assertSee('Batalkan Konfirmasi');
    }

    /** Pesanan marketplace: tombol pembatalan tampil di detail admin. */
    public function test_admin_order_detail_shows_revoke_action_for_success()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $order = $this->makeOrder($buyer, $product);
        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->id), ['status' => 'success']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->id))
            ->assertOk()
            ->assertSee('Batalkan Konfirmasi');
    }

    /** Pembatalan konfirmasi tercatat di log aktivitas dengan aksi khusus. */
    public function test_revocation_is_logged()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success']);

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'failed']);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'transaction.revoke',
            'subject_id' => $transaction->id,
        ]);
    }

    /** Setelah dibatalkan, transaksi bisa dibuka kembali ke pending seperti biasa. */
    public function test_revoked_transaction_can_be_reopened()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success']);
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'failed']);

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id), ['status' => 'pending'])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertFalse($transaction->wasRevoked()); // jejak audit dibersihkan
        $this->assertNull($transaction->revoked_at);
        $this->assertNull($transaction->revoked_by);
    }
}
