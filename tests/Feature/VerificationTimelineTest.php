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
 * Timeline verifikasi vertikal pada detail transaksi/pesanan pembeli.
 *
 * Memastikan langkah-langkah tampil sesuai keadaan pesanan: dibuat, tandai
 * bayar, bukti, verifikasi, dan hasil akhir — tanpa menyesatkan.
 */
class VerificationTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(User $user, Product $product, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'transaction_code' => 'TXN-TL-' . uniqid(),
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
            'order_code'     => 'MKT-TL-' . uniqid(),
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

    /** Transaksi pending baru: timeline menampilkan "Pesanan Dibuat". */
    public function test_transaction_timeline_shows_created_step()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="vtimeline"', $html);
        $this->assertStringContainsString('Pesanan Dibuat', $html);
        $this->assertStringContainsString('Menunggu Pembayaran', $html);
    }

    /** Setelah menandai bayar: langkah verifikasi admin muncul sebagai current. */
    public function test_transaction_timeline_shows_pending_verification_step()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());
        $transaction->update(['payment_claimed_at' => now()]);

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Kamu Menandai Sudah Bayar', $html);
        $this->assertStringContainsString('Menunggu Verifikasi Admin', $html);
        $this->assertStringContainsString('is-current', $html);
    }

    /** Transaksi selesai: langkah hasil akhir berstate success. */
    public function test_transaction_timeline_shows_success_result()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'status'              => 'success',
            'paid_at'             => now(),
            'payment_claimed_at'  => now(),
            'confirmed_at'        => now(),
            'confirmation_source' => OrderStatus::SOURCE_ADMIN,
            'confirmed_by'        => User::where('role', 'admin')->first()->id,
        ]);

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Pesanan Selesai', $html);
        $this->assertStringContainsString('is-success', $html);
    }

    /** Transaksi gagal: alasan tampil di langkah hasil akhir. */
    public function test_transaction_timeline_shows_failure_reason()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'status'         => 'failed',
            'confirmed_at'   => now(),
            'failure_reason' => 'Nominal transfer tidak sesuai.',
        ]);

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Pesanan Gagal', $html);
        $this->assertStringContainsString('Nominal transfer tidak sesuai.', $html);
        $this->assertStringContainsString('is-failed', $html);
    }

    /** Bukti pembayaran tampil sebagai langkah tersendiri bila diunggah. */
    public function test_transaction_timeline_shows_proof_step()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'payment_claimed_at' => now(),
            'payment_proof'      => 'payment-proofs/contoh.jpg',
        ]);

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Bukti Pembayaran Diunggah', $html);
        $this->assertStringContainsString('Lihat bukti pembayaran', $html);
    }

    /** Jejak pembatalan konfirmasi tampil di timeline. */
    public function test_transaction_timeline_shows_revocation_step()
    {
        $buyer = $this->buyer();
        $admin = User::where('role', 'admin')->first();

        $transaction = $this->makeTransaction($buyer, Product::first(), [
            'status'         => 'failed',
            'confirmed_at'   => now(),
            'revoked_at'     => now(),
            'revoked_by'     => $admin->id,
        ]);

        $html = $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Konfirmasi Dibatalkan', $html);
    }

    /** Detail pesanan marketplace juga memuat timeline verifikasi. */
    public function test_marketplace_order_timeline_renders()
    {
        $buyer = $this->buyer();
        $order = $this->makeOrder($buyer, MarketplaceProduct::where('stock', '>', 0)->first());

        $this->actingAs($buyer)
            ->get(route('orders.show', $order->order_code))
            ->assertOk()
            ->assertSee('Jejak Verifikasi')
            ->assertSee('Pesanan Dibuat');
    }
}
