<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Alur konfirmasi pesanan: pembeli menandai sudah bayar, admin yang
 * memutuskan hasil akhirnya (selesai atau gagal).
 */
class OrderConfirmationTest extends TestCase
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

    /** Pembeli menandai sudah bayar: status tetap menunggu konfirmasi admin. */
    public function test_customer_claim_keeps_status_pending()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code))
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertNotNull($transaction->payment_claimed_at);
        $this->assertTrue($transaction->needsReview());
        $this->assertNull($transaction->confirmed_at);
    }

    /** Admin mengonfirmasi transaksi: status menjadi selesai + jejak verifikasi. */
    public function test_admin_confirmation_marks_transaction_success()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success'])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('success', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertSame($admin->id, $transaction->confirmed_by);
        $this->assertNotNull($transaction->paid_at);
    }

    /** Admin menolak transaksi: status gagal, stok kembali, alasan tercatat. */
    public function test_admin_rejection_marks_transaction_failed_and_restores_stock()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $stockBefore = $product->stock;

        $transaction = $this->makeTransaction($buyer, $product);
        $product->decrement('stock'); // simulasikan stok yang ditahan saat checkout

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), [
                'status'         => 'failed',
                'failure_reason' => 'Bukti transfer tidak ditemukan.',
            ])
            ->assertRedirect();

        $transaction->refresh();

        $this->assertSame('failed', $transaction->status);
        $this->assertSame('Bukti transfer tidak ditemukan.', $transaction->failure_reason);
        $this->assertSame($admin->id, $transaction->confirmed_by);
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    /** Admin mengonfirmasi pesanan marketplace: selesai + jejak verifikasi. */
    public function test_admin_confirmation_marks_marketplace_order_success()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $order = $this->makeOrder($buyer, $product);

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->id), ['status' => 'success'])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame($admin->id, $order->confirmed_by);
        $this->assertNotNull($order->confirmed_at);
    }

    /** Admin menolak pesanan marketplace: stok tiap item dikembalikan ke penjual. */
    public function test_admin_rejection_restores_marketplace_stock()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $stockBefore = $product->stock;

        $order = $this->makeOrder($buyer, $product);
        $product->decrement('stock');

        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order->id), [
                'status'         => 'failed',
                'failure_reason' => 'Stok tidak tersedia.',
            ])
            ->assertRedirect();

        $this->assertSame('failed', $order->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    /** Status akhir tidak bisa diubah lagi (stok & laporan tetap konsisten). */
    public function test_final_status_cannot_be_changed_again()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $transaction->update(['status' => 'success']);

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('success', $transaction->fresh()->status);
    }

    /** Pembeli tidak boleh memverifikasi pesanannya sendiri. */
    public function test_buyer_cannot_confirm_own_order()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success'])
            ->assertStatus(403);

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    /** Dashboard admin menampilkan jumlah pesanan yang perlu diverifikasi. */
    public function test_admin_dashboard_shows_orders_needing_review()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $claimed = $this->makeTransaction($buyer, $product);
        $claimed->update(['payment_claimed_at' => now()]);

        $this->makeTransaction($buyer, $product); // belum ditandai bayar

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('stats')['review']);
    }

    /** Halaman status top up menampilkan keadaan "menunggu verifikasi admin". */
    public function test_topup_success_page_renders_claimed_state()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Saya Sudah Bayar');

        $transaction->update(['payment_claimed_at' => now()]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi Admin')
            ->assertSee('Pembayaran Sedang Diverifikasi');
    }

    /** Halaman status marketplace menampilkan keadaan "menunggu verifikasi admin". */
    public function test_marketplace_success_page_renders_claimed_state()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $order = $this->makeOrder($buyer, $product);

        $order->update(['payment_claimed_at' => now()]);

        $this->actingAs($buyer)
            ->get(route('marketplace.success', $order->order_code))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi Admin');
    }

    /** Admin melihat penanda "Sudah Bayar" dan tombol aksi pada baris pending. */
    public function test_admin_transactions_page_highlights_claimed_rows()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $transaction = $this->makeTransaction($buyer, $product);
        $transaction->update(['payment_claimed_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('Sudah Bayar')
            ->assertSee('Konfirmasi')
            ->assertSee('Perlu Diverifikasi');
    }

    /** Alasan gagal yang diisi admin tampil di halaman status pembeli. */
    public function test_failure_reason_is_visible_to_buyer()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $product = Product::first();
        $transaction = $this->makeTransaction($buyer, $product);

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), [
                'status'         => 'failed',
                'failure_reason' => 'Nominal transfer tidak sesuai.',
            ]);

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Nominal transfer tidak sesuai.');
    }
}
