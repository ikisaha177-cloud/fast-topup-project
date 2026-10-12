<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Halaman detail pesanan di sisi admin + aksi "buka kembali" untuk
 * memperbaiki salah klik (keputusan gagal/dibatalkan kembali menunggu
 * konfirmasi, dengan stok dikunci ulang).
 */
class AdminOrderDetailTest extends TestCase
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

    private function makeTransaction(User $user, Product $product): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TXN-TEST-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);
    }

    private function makeOrder(User $buyer, MarketplaceProduct $product): MarketplaceOrder
    {
        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-TEST-' . uniqid(),
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

    /** Halaman detail transaksi menampilkan data, jejak verifikasi & riwayat. */
    public function test_admin_can_view_transaction_detail_page()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $transaction->id))
            ->assertOk()
            ->assertSee($transaction->transaction_code, false)
            ->assertSee('Jejak Verifikasi')
            ->assertSee('Riwayat Aktivitas')
            ->assertSee('Konfirmasi &amp; Selesaikan', false);
    }

    /** Halaman detail pesanan marketplace menampilkan item + aksi verifikasi. */
    public function test_admin_can_view_order_detail_page()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $order = $this->makeOrder($buyer, $product);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->id))
            ->assertOk()
            ->assertSee($order->order_code, false)
            ->assertSee('Item Pesanan')
            ->assertSee($product->name, false);
    }

    /** Pembeli tidak boleh membuka halaman detail milik admin. */
    public function test_buyer_cannot_open_admin_detail_page()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($buyer)
            ->get(route('admin.transactions.show', $transaction->id))
            ->assertStatus(403);
    }

    /** Salah klik "Gagal" bisa dibuka kembali: pending lagi + stok dikunci ulang. */
    public function test_reopening_failed_transaction_returns_pending_and_relocks_stock()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $stockBefore = $product->stock;

        $transaction = $this->makeTransaction($buyer, $product);
        $product->decrement('stock'); // stok ditahan saat checkout

        $this->actingAs($admin)->post(route('admin.transactions.status', $transaction->id), [
            'status'         => 'failed',
            'failure_reason' => 'Salah input.',
        ])->assertRedirect();

        $this->assertSame($stockBefore, $product->fresh()->stock); // sudah dikembalikan

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id))
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertSame($stockBefore - 1, $product->fresh()->stock); // ditahan lagi
        $this->assertNull($transaction->failure_reason);
        $this->assertNull($transaction->confirmed_at);
        $this->assertNull($transaction->confirmed_by);
        $this->assertNull($transaction->confirmation_source);
        $this->assertNull($transaction->payment_claimed_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'transaction.reopen']);
    }

    /** Buka kembali gagal total bila stok sudah terjual habis (tidak boleh minus). */
    public function test_reopen_is_blocked_when_product_stock_is_gone()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $transaction->update(['status' => 'failed']);

        $product->update(['stock' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id))
            ->assertSessionHas('error');

        $this->assertSame('failed', $transaction->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock);
    }

    /** Keputusan sukses tidak bisa di-buka ulang dari halaman detail. */
    public function test_reopen_is_rejected_for_successful_transaction()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $transaction->update(['status' => 'success', 'confirmed_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id))
            ->assertSessionHas('error');

        $this->assertSame('success', $transaction->fresh()->status);
    }

    /** Aksi buka kembali hanya menerima status pending (salah pakai form). */
    public function test_reopen_only_accepts_pending_payload()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $transaction->update(['status' => 'failed']);

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id), ['status' => 'success'])
            ->assertSessionHasErrors('status');

        $this->assertSame('failed', $transaction->fresh()->status);
    }

    /** Pembeli tidak boleh membuka ulang keputusan admin. */
    public function test_buyer_cannot_reopen_transaction()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $transaction->update(['status' => 'failed']);

        $this->actingAs($buyer)
            ->post(route('admin.transactions.reopen', $transaction->id))
            ->assertStatus(403);

        $this->assertSame('failed', $transaction->fresh()->status);
    }

    /** Buka ulang pesanan marketplace: stok tiap item dikunci ulang. */
    public function test_reopening_failed_marketplace_order_relocks_item_stock()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $stockBefore = $product->stock;

        $order = $this->makeOrder($buyer, $product);
        $product->decrement('stock');

        $this->actingAs($admin)->post(route('admin.orders.status', $order->id), [
            'status' => 'failed',
        ])->assertRedirect();

        $this->assertSame($stockBefore, $product->fresh()->stock);

        $this->actingAs($admin)
            ->post(route('admin.orders.reopen', $order->id))
            ->assertRedirect();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame($stockBefore - 1, $product->fresh()->stock);
        $this->assertDatabaseHas('activity_logs', ['action' => 'order.reopen']);
    }

    /** Buka ulang pesanan marketplace dibatalkan bila stok item tidak cukup. */
    public function test_reopen_marketplace_order_blocked_when_stock_insufficient()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $order = $this->makeOrder($buyer, $product);
        $order->update(['status' => 'failed']);

        $product->update(['stock' => 0]);

        $this->actingAs($admin)
            ->post(route('admin.orders.reopen', $order->id))
            ->assertSessionHas('error');

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame(0, $product->fresh()->stock);
    }

    /** Baris tabel admin tertaut ke halaman detail. */
    public function test_admin_list_links_to_detail_pages()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($this->admin())
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee(route('admin.transactions.show', $transaction->id), false)
            ->assertSee('Detail');
    }
}
