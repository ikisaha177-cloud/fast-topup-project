<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kebijakan verifikasi pembayaran (config payment.verification):
 *
 *   hybrid (default) : bukti pembayaran -> otomatis Selesai; tanpa bukti -> admin.
 *   manual           : selalu menunggu keputusan admin.
 *   auto             : setiap klaim "sudah bayar" -> otomatis Selesai.
 *
 * Jejak audit wajib jujur: konfirmasi otomatis dicatat `confirmation_source`
 * = auto dan TIDAK mengisi confirmed_by (bukan admin).
 */
class PaymentVerificationModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('local');

        $this->seed();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(User $user, Product $product): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TXN-MODE-' . uniqid(),
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
            'order_code'     => 'MKT-MODE-' . uniqid(),
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

    /** Mode default (hybrid): unggah bukti -> langsung Selesai. */
    public function test_hybrid_auto_confirms_when_proof_uploaded()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 640, 480),
            ])
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('success', $transaction->status);
        $this->assertSame(OrderStatus::SOURCE_AUTO, $transaction->confirmation_source);
        $this->assertNull($transaction->confirmed_by);
        $this->assertTrue($transaction->confirmedByAuto());
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->hasPaymentProof());
        Storage::disk('local')->assertExists($transaction->payment_proof);
    }

    /** Mode default (hybrid): tanpa bukti -> tetap menunggu admin. */
    public function test_hybrid_waits_for_admin_without_proof()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertNotNull($transaction->payment_claimed_at);
        $this->assertTrue($transaction->needsReview());
        $this->assertNull($transaction->confirmation_source);
    }

    /** Mode manual: bukti hanya lampiran, status tetap pending. */
    public function test_manual_mode_keeps_pending_even_with_proof()
    {
        config(['payment.verification' => 'manual']);

        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 640, 480),
            ]);

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertTrue($transaction->hasPaymentProof());
        $this->assertNull($transaction->confirmation_source);
    }

    /** Mode auto: klaim tanpa bukti pun langsung Selesai. */
    public function test_auto_mode_confirms_without_proof()
    {
        config(['payment.verification' => 'auto']);

        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('success', $transaction->status);
        $this->assertSame(OrderStatus::SOURCE_AUTO, $transaction->confirmation_source);
        $this->assertNull($transaction->confirmed_by);
    }

    /** Marketplace memakai kebijakan yang sama: bukti -> otomatis Selesai. */
    public function test_marketplace_hybrid_auto_confirms_with_proof()
    {
        $buyer = $this->buyer();
        $order = $this->makeOrder($buyer, MarketplaceProduct::where('stock', '>', 0)->first());

        $this->actingAs($buyer)
            ->post(route('marketplace.payment.claim', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 720, 540),
            ])
            ->assertRedirect(route('marketplace.success', $order->order_code));

        $order->refresh();

        $this->assertSame('success', $order->status);
        $this->assertSame(OrderStatus::SOURCE_AUTO, $order->confirmation_source);
        $this->assertTrue($order->confirmedByAuto());
    }

    /** Stok tetap terkunci saat auto-konfirmasi (tidak dikembalikan). */
    public function test_auto_confirm_keeps_stock_reserved()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $stockBefore = (int) $product->stock;

        $transaction = $this->makeTransaction($buyer, $product);
        $product->decrement('stock'); // simulasi stok yang sudah ditahan saat checkout

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 640, 480),
            ]);

        $this->assertSame('success', $transaction->fresh()->status);
        $this->assertSame($stockBefore - 1, (int) $product->fresh()->stock);
    }

    /** Invoice transaksi bisa dibuka pembeli & menampilkan detail + cap LUNAS. */
    public function test_transaction_invoice_renders_for_owner()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());
        $transaction->update([
            'status'              => 'success',
            'paid_at'             => now(),
            'confirmed_at'        => now(),
            'confirmation_source' => OrderStatus::SOURCE_AUTO,
        ]);

        $this->actingAs($buyer)
            ->get(route('invoice.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Invoice')
            ->assertSee($transaction->transaction_code)
            ->assertSee('Lunas');
    }

    /** Invoice pending menampilkan instruksi bayar, bukan cap LUNAS. */
    public function test_pending_invoice_shows_payment_instructions()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->get(route('invoice.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Selesaikan pembayaran')
            ->assertDontSee('>Lunas<', false);
    }

    /** Invoice marketplace: pemilik boleh, pembeli lain tidak. */
    public function test_marketplace_invoice_is_authorized()
    {
        $owner = $this->buyer();
        $other = $this->buyer();
        $order = $this->makeOrder($owner, MarketplaceProduct::where('stock', '>', 0)->first());

        $this->actingAs($owner)
            ->get(route('invoice.show', $order->order_code))
            ->assertOk()
            ->assertSee($order->order_code);

        $this->actingAs($other)
            ->get(route('invoice.show', $order->order_code))
            ->assertForbidden();
    }

    /** Invoice transaksi guest (tanpa akun) boleh dibuka siapa pun yang tahu kode. */
    public function test_guest_transaction_invoice_is_public()
    {
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $transaction = Transaction::create([
            'transaction_code' => 'TXN-GUEST-' . uniqid(),
            'user_id'          => null,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '99999999',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);

        $this->get(route('invoice.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee($transaction->transaction_code);
    }
}
