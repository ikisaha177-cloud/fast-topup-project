<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Kode promo: admin CRUD, validasi di checkout top up & marketplace,
 * dan jejak potongan yang tercatat di transaksi/pesanan/invoice.
 */
class PromoCodeTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return \App\Models\PromoCode
     */
    private function promo(array $overrides = [])
    {
        return PromoCode::create(array_merge([
            'code'      => 'GANTINO10',
            'type'      => 'percent',
            'value'     => 10,
            'scope'     => 'all',
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Isi form top up sampai masuk halaman checkout.
     *
     * @return \App\Models\Product
     */
    private function startTopupCheckout(User $user)
    {
        $game    = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->orderBy('price')->first();

        $this->actingAs($user)
            ->post(route('topup.checkout.store'), [
                'game_slug'    => $game->slug,
                'user_game_id' => '12345678',
                'product_id'   => $product->id,
            ])
            ->assertRedirect(route('topup.checkout'));

        return $product;
    }

    /* ---------------------------------------------------------------
     |  Panel admin
     * -------------------------------------------------------------- */

    public function test_admin_can_create_percent_promo()
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.promos.store'), [
            'code'      => 'diskon10',
            'type'      => 'percent',
            'value'     => 10,
            'scope'     => 'all',
            'is_active' => 1,
        ])->assertRedirect(route('admin.promos.index'));

        // Kode dinormalkan ke huruf besar agar pembeli tidak salah ketik huruf besar/kecil.
        $this->assertDatabaseHas('promo_codes', [
            'code'      => 'DISKON10',
            'type'      => 'percent',
            'value'     => 10,
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_create_fixed_promo_with_cap()
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.promos.store'), [
            'code'          => 'POTONGAN5000',
            'type'          => 'fixed',
            'value'         => 5000,
            'min_spend'     => 50000,
            'max_discount'  => 5000,
            'scope'         => 'topup',
            'usage_limit'   => 25,
            'is_active'     => 1,
        ])->assertRedirect(route('admin.promos.index'));

        $this->assertDatabaseHas('promo_codes', [
            'code'         => 'POTONGAN5000',
            'type'         => 'fixed',
            'value'        => 5000,
            'min_spend'    => 50000,
            'max_discount' => 5000,
            'scope'        => 'topup',
            'usage_limit'  => 25,
        ]);
    }

    public function test_percent_promo_above_100_is_rejected()
    {
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($admin)->post(route('admin.promos.store'), [
            'code'  => 'TERLALUBESAR',
            'type'  => 'percent',
            'value' => 150,
            'scope' => 'all',
        ])->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('promo_codes', ['code' => 'TERLALUBESAR']);
    }

    public function test_duplicate_promo_code_is_rejected_even_with_different_case()
    {
        $admin = User::where('role', 'admin')->first();

        $this->promo(['code' => 'HEMAT10']);

        $this->actingAs($admin)->post(route('admin.promos.store'), [
            'code'  => 'hemat10',
            'type'  => 'percent',
            'value' => 5,
            'scope' => 'all',
        ])->assertSessionHasErrors('code');
    }

    public function test_buyer_cannot_manage_promos()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.promos.index'))->assertStatus(403);
        $this->actingAs($buyer)->get(route('admin.promos.create'))->assertStatus(403);

        $promo = $this->promo();

        $this->actingAs($buyer)->delete(route('admin.promos.destroy', $promo->id))->assertStatus(403);
    }

    public function test_admin_can_toggle_promo_status()
    {
        $admin = User::where('role', 'admin')->first();
        $promo = $this->promo(['code' => 'BISA', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.promos.toggle', $promo->id))
            ->assertRedirect();

        $this->assertFalse((bool) $promo->fresh()->is_active);
    }

    public function test_admin_can_view_and_edit_promo_pages()
    {
        $admin = User::where('role', 'admin')->first();
        $promo = $this->promo(['code' => 'SITUS10']);

        $this->actingAs($admin)->get(route('admin.promos.index'))
            ->assertOk()
            ->assertSee('SITUS10');

        $this->actingAs($admin)->get(route('admin.promos.create'))->assertOk();

        $this->actingAs($admin)->get(route('admin.promos.edit', $promo->id))
            ->assertOk()
            ->assertSee('SITUS10');
    }

    public function test_promo_that_has_been_used_cannot_be_deleted()
    {
        $admin = User::where('role', 'admin')->first();
        $promo = $this->promo(['code' => 'TERPAKAI', 'used_count' => 3]);

        $this->actingAs($admin)->delete(route('admin.promos.destroy', $promo->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('promo_codes', ['code' => 'TERPAKAI']);
    }

    public function test_unused_promo_can_be_deleted()
    {
        $admin = User::where('role', 'admin')->first();
        $promo = $this->promo(['code' => 'BELUMPAKAI']);

        $this->actingAs($admin)->delete(route('admin.promos.destroy', $promo->id))
            ->assertRedirect(route('admin.promos.index'));

        $this->assertDatabaseMissing('promo_codes', ['code' => 'BELUMPAKAI']);
    }

    /* ---------------------------------------------------------------
     |  Validasi saat pembeli memasang kode promo
     * -------------------------------------------------------------- */

    public function test_valid_promo_is_applied_to_topup_draft_and_shown_on_checkout()
    {
        $this->promo(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10]);

        $user    = User::factory()->create();
        $product = $this->startTopupCheckout($user);

        $this->actingAs($user)
            ->post(route('topup.promo.apply'), ['code' => 'hemat10'])
            ->assertRedirect(route('topup.checkout'))
            ->assertSessionHasNoErrors();

        $discount = (int) floor($product->price * 10 / 100);

        $this->actingAs($user)
            ->get(route('topup.checkout'))
            ->assertOk()
            ->assertSee('HEMAT10')
            ->assertSee(number_format($product->price - $discount, 0, ',', '.'));
    }

    public function test_buyer_can_remove_applied_promo()
    {
        $this->promo(['code' => 'HEMAT10']);

        $user    = User::factory()->create();
        $product = $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.promo.apply'), ['code' => 'HEMAT10'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('topup.promo.apply'), ['remove' => 1])
            ->assertRedirect(route('topup.checkout'));

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();

        $this->assertSame($product->price, (int) $transaction->amount);
        $this->assertSame(0, (int) $transaction->discount_amount);
        $this->assertNull($transaction->promo_code);
        $this->assertSame(0, (int) PromoCode::where('code', 'HEMAT10')->first()->used_count);
    }

    /**
     * @dataProvider rejectedPromoProvider
     */
    public function test_unusable_promo_is_rejected(array $overrides, $submittedCode)
    {
        // Baris promo yang memang ada — kecuali kasus "kode tidak ada".
        if ($overrides !== []) {
            $this->promo($overrides);
        }

        $user = User::factory()->create();
        $this->startTopupCheckout($user);

        $this->actingAs($user)
            ->post(route('topup.promo.apply'), ['code' => $submittedCode])
            ->assertSessionHasErrors('promo_code');

        $this->assertNull(session('topup_draft')['promo_code'] ?? null);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public function rejectedPromoProvider()
    {
        return [
            'kode tidak ada' => [
                [], 'TIDAKADA',
            ],
            'nonaktif' => [
                ['code' => 'TOLAK', 'is_active' => false], 'TOLAK',
            ],
            'belum dimulai' => [
                ['code' => 'TOLAK', 'starts_at' => now()->addDay()], 'TOLAK',
            ],
            'sudah kedaluwarsa' => [
                ['code' => 'TOLAK', 'ends_at' => now()->subDay()], 'TOLAK',
            ],
            'kuota habis' => [
                ['code' => 'TOLAK', 'usage_limit' => 2, 'used_count' => 2], 'TOLAK',
            ],
            'cakupan marketplace saja' => [
                ['code' => 'TOLAK', 'scope' => 'marketplace'], 'TOLAK',
            ],
            'minimal belanja belum tercapai' => [
                ['code' => 'TOLAK', 'min_spend' => 99999999], 'TOLAK',
            ],
            'nilai nol' => [
                ['code' => 'TOLAK', 'type' => 'fixed', 'value' => 0], 'TOLAK',
            ],
            'format tidak valid' => [
                ['code' => 'TOLAK'], '!!',
            ],
        ];
    }

    public function test_marketplace_promo_cannot_be_used_on_topup()
    {
        $this->promo(['code' => 'KHUSUSMKT', 'scope' => 'marketplace']);

        $user = User::factory()->create();
        $this->startTopupCheckout($user);

        $this->actingAs($user)
            ->post(route('topup.promo.apply'), ['code' => 'KHUSUSMKT'])
            ->assertSessionHasErrors('promo_code');
    }

    public function test_topup_promo_cannot_be_used_on_marketplace()
    {
        $this->promo(['code' => 'KHUSUSTOPUP', 'scope' => 'topup']);

        $user     = User::factory()->create();
        $product  = MarketplaceProduct::where('stock', '>', 0)->orderBy('price')->first();

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($user)
            ->post(route('marketplace.promo.apply'), ['code' => 'KHUSUSTOPUP'])
            ->assertSessionHasErrors('promo_code');

        $this->assertNull(session('promo_code'));
    }

    /* ---------------------------------------------------------------
     |  Efek ke harga transaksi & pesanan
     * -------------------------------------------------------------- */

    public function test_topup_payment_records_discount_and_counts_usage()
    {
        $this->promo(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10]);

        $user    = User::factory()->create();
        $product = $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.promo.apply'), ['code' => 'HEMAT10']);

        $this->actingAs($user)
            ->post(route('topup.pay'), ['payment_method' => 'QRIS'])
            ->assertRedirect(route('topup.success', Transaction::first()->transaction_code));

        $transaction = Transaction::first();
        $discount    = (int) floor($product->price * 10 / 100);

        $this->assertSame($product->price - $discount, (int) $transaction->amount);
        $this->assertSame($discount, (int) $transaction->discount_amount);
        $this->assertSame('HEMAT10', $transaction->promo_code);
        $this->assertSame(1, (int) PromoCode::where('code', 'HEMAT10')->first()->used_count);
    }

    public function test_topup_payment_without_promo_keeps_full_amount()
    {
        $user    = User::factory()->create();
        $product = $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();

        $this->assertSame($product->price, (int) $transaction->amount);
        $this->assertSame(0, (int) $transaction->discount_amount);
        $this->assertNull($transaction->promo_code);
    }

    public function test_marketplace_checkout_records_discount_and_counts_usage()
    {
        $this->promo(['code' => 'MKT10', 'type' => 'percent', 'value' => 10, 'scope' => 'marketplace']);

        $user     = User::factory()->create();
        $product  = MarketplaceProduct::where('stock', '>', 0)->orderBy('price')->first();

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->actingAs($user)
            ->post(route('marketplace.promo.apply'), ['code' => 'mkt10'])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS'])
            ->assertRedirect(route('marketplace.success', MarketplaceOrder::first()->order_code));

        $order    = MarketplaceOrder::first();
        $subtotal = $product->price * 2;
        $discount = (int) floor($subtotal * 10 / 100);

        $this->assertSame($subtotal - $discount, (int) $order->total_amount);
        $this->assertSame($discount, (int) $order->discount_amount);
        $this->assertSame('MKT10', $order->promo_code);
        $this->assertSame(1, (int) PromoCode::where('code', 'MKT10')->first()->used_count);

        // Potongan terlihat di halaman sukses pembeli.
        $this->actingAs($user)
            ->get(route('marketplace.success', $order->order_code))
            ->assertOk()
            ->assertSee('MKT10');
    }

    public function test_marketplace_checkout_without_promo_keeps_full_total()
    {
        $user    = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->orderBy('price')->first();

        $this->actingAs($user)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($user)
            ->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = MarketplaceOrder::first();

        $this->assertSame($product->price, (int) $order->total_amount);
        $this->assertSame(0, (int) $order->discount_amount);
        $this->assertNull($order->promo_code);
    }

    public function test_expired_promo_is_dropped_when_checkout_page_reopens()
    {
        $promo = $this->promo(['code' => 'HEMAT10']);

        $user = User::factory()->create();
        $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.promo.apply'), ['code' => 'HEMAT10'])
            ->assertSessionHasNoErrors();

        // Promo kedaluwarsa setelah dipasang: checkout harus jujur dan melepasnya.
        // (GET pertama di sini juga menghabiskan pesan sukses yang tersimpan di flash.)
        $this->actingAs($user)->get(route('topup.checkout'))->assertOk()->assertSee('HEMAT10');

        $promo->update(['ends_at' => now()->subMinute()]);

        $this->actingAs($user)
            ->get(route('topup.checkout'))
            ->assertOk()
            ->assertDontSee('HEMAT10');

        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();

        $this->assertSame(0, (int) $transaction->discount_amount);
        $this->assertNull($transaction->promo_code);
    }

    /* ---------------------------------------------------------------
     |  Perhitungan & dokumen
     * -------------------------------------------------------------- */

    public function test_percent_discount_is_capped_by_max_discount()
    {
        $promo = $this->promo(['code' => 'DIBATASI', 'value' => 50, 'max_discount' => 5000]);

        $this->assertSame(5000, $promo->discountFor(1000000));
    }

    public function test_discount_never_exceeds_the_amount()
    {
        $promo = $this->promo(['code' => 'GILAAN', 'type' => 'fixed', 'value' => 99999999]);

        $this->assertSame(22000, $promo->discountFor(22000));
        $this->assertSame(0, $promo->discountFor(0));
    }

    public function test_invoice_shows_promo_discount()
    {
        $this->promo(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10]);

        $user = User::factory()->create();
        $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.promo.apply'), ['code' => 'HEMAT10']);
        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $this->get(route('invoice.show', Transaction::first()->transaction_code))
            ->assertOk()
            ->assertSee('HEMAT10')
            ->assertSee('Potongan promo');
    }

    public function test_payment_portal_receipt_shows_promo_discount()
    {
        $this->promo(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10]);

        $user = User::factory()->create();
        $this->startTopupCheckout($user);

        $this->actingAs($user)->post(route('topup.promo.apply'), ['code' => 'HEMAT10']);
        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        // Struk baru muncul setelah pembeli menandai sudah bayar.
        $this->actingAs($user)
            ->post(route('topup.payment.claim', Transaction::first()->transaction_code));

        $this->get(route('payment.show', Transaction::first()->transaction_code))
            ->assertOk()
            ->assertSee('HEMAT10')
            ->assertSee('Potongan Promo');
    }

    public function test_service_rejects_malformed_promo_code()
    {
        $quote = app(\App\Services\PromoService::class)->quote('!!', 100000, 'topup');

        $this->assertFalse($quote['ok']);
        $this->assertSame(0, $quote['discount']);
    }
}
