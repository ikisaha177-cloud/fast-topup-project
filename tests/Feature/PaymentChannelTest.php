<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentChannel;
use App\Support\PaymentMethods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1 #5: perluasan kanal bayar — Alfamart/Indomaret (tunai di kasir)
 * dan Virtual Account per bank (BCA, Mandiri), semuanya simulasi.
 */
class PaymentChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(User $user, string $method): Transaction
    {
        $product = Product::first();

        return Transaction::create([
            'transaction_code' => 'TXN-CHANNEL-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => $method,
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);
    }

    public function test_daftar_metode_mencakup_retail_dan_va_tambahan()
    {
        $baru = [
            PaymentMethods::ALFAMART,
            PaymentMethods::INDOMARET,
            PaymentMethods::BANK_BCA,
            PaymentMethods::BANK_MANDIRI,
        ];

        foreach ($baru as $metode) {
            $this->assertContains($metode, PaymentMethods::all());
            $this->assertArrayHasKey($metode, PaymentMethods::labelled());
        }

        $this->assertCount(count(PaymentMethods::all()), PaymentMethods::labelled());
    }

    public function test_checkout_topup_menerima_metode_retail()
    {
        $user = $this->buyer();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ])
            ->assertRedirect(route('topup.checkout'));

        $this->actingAs($user)
            ->post(route('topup.pay'), ['payment_method' => PaymentMethods::ALFAMART])
            ->assertRedirect();

        $this->assertSame(PaymentMethods::ALFAMART, Transaction::first()->payment_method);
    }

    public function test_portal_alfamart_menampilkan_kode_pembayaran_deterministik()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, PaymentMethods::ALFAMART);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Bayar tunai di Alfamart')
            ->assertSee('Kode Pembayaran')
            ->assertSee(PaymentChannel::paymentCode($transaction->transaction_code), false)
            ->assertSee('simpan struk dari kasir', false);
    }

    public function test_portal_indomaret_menampilkan_instruksi_gerai()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, PaymentMethods::INDOMARET);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Bayar tunai di Indomaret')
            ->assertSee('Datang ke gerai Indomaret', false)
            ->assertSee(PaymentChannel::paymentCode($transaction->transaction_code), false);
    }

    public function test_portal_va_bca_memakai_awalan_dan_judul_khusus_bank()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, PaymentMethods::BANK_BCA);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Transfer ke Virtual Account BCA')
            ->assertSee(PaymentChannel::virtualAccount($transaction->transaction_code, '3901'), false);
    }

    public function test_portal_va_mandiri_memakai_awalan_khusus_bank()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, PaymentMethods::BANK_MANDIRI);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Transfer ke Virtual Account Mandiri')
            ->assertSee(PaymentChannel::virtualAccount($transaction->transaction_code, '8890'), false);
    }

    public function test_teks_va_bank_transfer_generik_tetap_persis_sama()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, PaymentMethods::BANK_TRANSFER);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Transfer ke Virtual Account')
            ->assertSee('Nomor Virtual Account')
            ->assertSee(PaymentChannel::virtualAccount($transaction->transaction_code), false);
    }

    public function test_nomor_va_berbeda_per_bank_dan_selalu_deterministik()
    {
        $code = 'TXN-SAMPLE-1';

        $this->assertSame(
            PaymentChannel::virtualAccount($code, '3901'),
            PaymentChannel::virtualAccount($code, '3901')
        );
        $this->assertNotSame(
            PaymentChannel::virtualAccount($code, '3901'),
            PaymentChannel::virtualAccount($code, '8890')
        );

        // Kode pembayaran retail: 12 digit (14 karakter dengan 2 spasi), stabil.
        $paymentCode = PaymentChannel::paymentCode($code);
        $this->assertSame($paymentCode, PaymentChannel::paymentCode($code));
        $this->assertSame(14, strlen($paymentCode));
        $this->assertSame(12, strlen(str_replace(' ', '', $paymentCode)));
    }

    public function test_channel_retail_dan_va_mempunyai_jenis_yang_tepat()
    {
        $retail = PaymentChannel::forMethod(PaymentMethods::INDOMARET, 'TXN-A', 50000);
        $bca = PaymentChannel::forMethod(PaymentMethods::BANK_BCA, 'TXN-A', 50000);

        $this->assertSame(PaymentChannel::TYPE_RETAIL, $retail['type']);
        $this->assertSame(PaymentChannel::TYPE_BANK, $bca['type']);
        $this->assertStringContainsString('Indomaret', $retail['headline']);
        $this->assertStringContainsString('BCA', $bca['headline']);
    }
}
