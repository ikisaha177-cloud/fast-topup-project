<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Notifications\TransactionStatusChanged;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /** Registrasi mengirim email selamat datang. */
    public function test_register_sends_welcome_notification()
    {
        Notification::fake();

        $this->post(route('register'), [
            'name'                  => 'Pembeli Baru',
            'email'                 => 'baru@example.test',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('home'));

        $user = User::where('email', 'baru@example.test')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    /** Konfirmasi pembayaran top up mengirim notifikasi status. */
    public function test_topup_confirm_sends_notification()
    {
        Notification::fake();

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

        $this->actingAs($user)
            ->post(route('topup.payment.confirm', $transaction->transaction_code));

        Notification::assertSentTo($user, TransactionStatusChanged::class);
    }

    /** Konfirmasi pembayaran marketplace mengirim notifikasi status. */
    public function test_marketplace_confirm_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->actingAs($user)->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);
        $this->actingAs($user)->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = \App\Models\MarketplaceOrder::first();

        $this->actingAs($user)
            ->post(route('marketplace.payment.confirm', $order->order_code));

        Notification::assertSentTo($user, OrderStatusChanged::class);
    }
}
