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

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    public function test_topup_pay_creates_pending_transaction_and_reserves_stock()
    {
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();
        $stockBefore = $product->stock;

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ])
            ->assertRedirect(route('topup.checkout'));

        $this->actingAs($user)
            ->post(route('topup.pay'), ['payment_method' => 'QRIS'])
            ->assertRedirect(route('topup.success', Transaction::first()->transaction_code));

        $transaction = Transaction::first();

        $this->assertSame('pending', $transaction->status);
        $this->assertSame($stockBefore - 1, $product->fresh()->stock);
    }

    public function test_topup_claim_keeps_pending_until_admin_confirms()
    {
        $user = User::factory()->create();
        $admin = User::where('role', 'admin')->first();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ]);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'DANA']);

        $transaction = Transaction::first();

        // Pembeli menandai sudah bayar -> status tetap pending.
        $this->actingAs($user)
            ->post(route('topup.payment.claim', $transaction->transaction_code))
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();
        $this->assertSame('pending', $transaction->status);
        $this->assertNotNull($transaction->payment_claimed_at);

        // Admin yang menentukan hasil akhirnya.
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success'])
            ->assertRedirect();

        $transaction->refresh();
        $this->assertSame('success', $transaction->status);
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertSame($admin->id, $transaction->confirmed_by);
    }

    public function test_topup_cancel_restores_stock()
    {
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();
        $stockBefore = $product->stock;

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ]);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'OVO']);

        $transaction = Transaction::first();

        $this->actingAs($user)
            ->post(route('topup.payment.cancel', $transaction->transaction_code))
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    public function test_checkout_and_payment_use_current_catalog_price()
    {
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)->post(route('topup.checkout.store'), [
            'game_slug' => $game->slug,
            'user_game_id' => '12345678',
            'product_id' => $product->id,
        ]);

        $newPrice = $product->price + 1000;
        $product->update(['price' => $newPrice]);

        $this->get(route('topup.checkout'))
            ->assertOk()
            ->assertSee(number_format($newPrice, 0, ',', '.'), false);

        $this->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $this->assertSame($newPrice, Transaction::first()->amount);
    }

    public function test_topup_rejects_out_of_stock_product()
    {
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();
        $product->update(['stock' => 0]);

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ])
            ->assertSessionHasErrors('product_id');
    }

    public function test_marketplace_checkout_creates_pending_order_and_reserves_stock()
    {
        $user = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $stockBefore = $product->stock;

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->actingAs($user)
            ->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS'])
            ->assertRedirect(route('marketplace.success', MarketplaceOrder::first()->order_code));

        $order = MarketplaceOrder::first();

        $this->assertSame('pending', $order->status);
        $this->assertSame($stockBefore - 2, $product->fresh()->stock);
    }

    public function test_buyer_can_see_own_marketplace_orders()
    {
        $user = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($user)->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = MarketplaceOrder::first();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->order_code);

        $this->actingAs($user)
            ->get(route('orders.show', $order->order_code))
            ->assertOk();
    }

    public function test_admin_can_update_transaction_status()
    {
        $admin = User::where('role', 'admin')->first();
        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ]);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();

        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'success'])
            ->assertRedirect();

        $this->assertSame('success', $transaction->fresh()->status);
    }

    public function test_topup_pay_records_gateway_charge_information()
    {
        config(['payment.driver' => 'simulation']);

        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)->post(route('topup.checkout.store'), [
            'game_slug'    => $game->slug,
            'user_game_id' => '12345678',
            'product_id'   => $product->id,
        ]);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();

        $this->assertSame('simulation', $transaction->payment_provider);
        $this->assertNotNull($transaction->payment_reference);
        // Driver simulasi tidak mengarahkan ke halaman eksternal.
        $this->assertNull($transaction->payment_url);
    }

    public function test_marketplace_checkout_records_gateway_charge_information()
    {
        config(['payment.driver' => 'simulation']);

        $user = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($user)
            ->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = MarketplaceOrder::first();

        $this->assertSame('simulation', $order->payment_provider);
        $this->assertNotNull($order->payment_reference);
    }
}
