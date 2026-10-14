<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Kualitas tampilan katalog: gambar beraspek tetap, konten jujur
 * (tanpa rating/testimoni/klaim karangan), dan "Terjual" yang dihitung
 * dari pesanan berstatus Selesai.
 */
class CatalogPresentationTest extends TestCase
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

    /** Halaman publik tidak menampilkan rating / testimoni karangan. */
    public function test_public_pages_do_not_show_fabricated_social_proof()
    {
        foreach (['/', '/marketplace'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('4.9', $html, "Halaman {$url} masih memuat rating karangan.");
            $this->assertStringNotContainsString('class="rating"', $html);
            $this->assertStringNotContainsString('class="star"', $html);
            $this->assertStringNotContainsString('Penjual terverifikasi', $html);
            $this->assertStringNotContainsString('Rata-rata proses', $html);
            $this->assertStringNotContainsString('Kata Pengguna', $html);
            $this->assertStringNotContainsString('(terjual)', $html);
        }
    }

    /** Gambar produk memakai kotak rasio tetap 8:5 dan atribut dimensi. */
    public function test_product_images_use_fixed_ratio_box_and_dimension_attributes()
    {
        $html = $this->get('/marketplace')->assertOk()->getContent();

        $this->assertStringContainsString('class="product-thumb"', $html);
        $this->assertStringContainsString('width="480" height="300"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    /** Logo game punya atribut dimensi agar tidak menggeser layout. */
    public function test_game_logos_have_dimension_attributes()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="game-logo', $html);
        $this->assertStringContainsString('width="480" height="300"', $html);
    }

    /** "Terjual" dihitung dari item pada pesanan berstatus Selesai. */
    public function test_sold_count_only_counts_completed_orders()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 5)->first();

        $this->assertSame(0, (int) $product->sold_count);

        $this->actingAs($buyer)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 2]);

        $this->actingAs($buyer)
            ->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = MarketplaceOrder::first();

        // Masih pending -> belum terhitung terjual.
        $this->assertSame(0, (int) $product->fresh()->sold_count);

        $order->update(['status' => 'success']);

        $this->assertSame(2, (int) $product->fresh()->sold_count);
    }

    /** Scope withSoldCount tidak menimbulkan N+1 dan nilainya benar. */
    public function test_with_sold_count_scope_returns_accurate_values()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 5)->first();

        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-SOLD-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'success',
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 3,
            'price'                  => $product->price,
            'subtotal'               => $product->price * 3,
        ]);

        $loaded = MarketplaceProduct::whereKey($product->id)->withSoldCount()->first();

        $this->assertSame(3, (int) $loaded->sold_count);
    }

    /** Halaman keranjang menampilkan thumbnail produk. */
    public function test_cart_shows_product_thumbnail()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->actingAs($buyer)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($buyer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('product-thumb is-thumb', false)
            ->assertSee($product->name);
    }

    /** Kartu riwayat pesanan menampilkan thumbnail item. */
    public function test_order_history_shows_item_thumbnails()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->actingAs($buyer)
            ->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 1]);

        $this->actingAs($buyer)->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $this->actingAs($buyer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('order-thumbs', false)
            ->assertSee('product-thumb is-thumb', false);
    }

    /** Detail transaksi memakai label sumber verifikasi, bukan selalu "Admin". */
    public function test_transaction_detail_uses_confirmation_source_label()
    {
        $buyer = $this->buyer();
        $product = \App\Models\Product::first();

        $transaction = \App\Models\Transaction::create([
            'transaction_code' => 'TXN-SRC-' . uniqid(),
            'user_id'          => $buyer->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'success',
            'confirmed_at'     => now(),
            'confirmation_source' => 'gateway',
        ]);

        $this->actingAs($buyer)
            ->get(route('transactions.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee($transaction->confirmationLabel(), false)
            ->assertDontSee('Diverifikasi Admin');
    }

    /** Halaman detail produk membawa meta share + data terstruktur (SEO). */
    public function test_product_detail_exposes_open_graph_and_structured_data()
    {
        $product = MarketplaceProduct::where('is_active', true)->firstOrFail();

        $html = $this->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('og:type" content="product"', $html);
        $this->assertStringContainsString('twitter:card', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"priceCurrency":"IDR"', $html);
        $this->assertStringContainsString('schema.org/InStock', $html);
    }

    /** Karakter berbahaya di nama/deskripsi produk di-escape hex agar tidak breakout script tag. */
    public function test_json_ld_escapes_script_tags_in_product_attributes()
    {
        $seller = User::factory()->create(['role' => 'user']);
        $product = MarketplaceProduct::create([
            'user_id'     => $seller->id,
            'name'        => 'Item </script><script>alert("xss")</script>',
            'slug'        => 'item-xss-test-' . uniqid(),
            'description' => 'Deskripsi dengan </script>',
            'price'       => 50000,
            'stock'       => 5,
            'is_active'   => true,
        ]);

        $html = $this->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->getContent();

        // Tag </script> literal TIDAK boleh muncul di dalam payload JSON-LD.
        $this->assertStringNotContainsString('</script><script>alert("xss")', $html);
        $this->assertStringContainsString('\u003C/script\u003E', $html);
    }

    /** Home membawa data terstruktur WebSite + tautan lompat aksesibilitas. */
    public function test_home_exposes_website_structured_data_and_skip_link()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"WebSite"', $html);
        $this->assertStringContainsString('class="skip-link"', $html);
        $this->assertStringContainsString('<main id="main"', $html);
        $this->assertStringContainsString('favicon.svg', $html);
        $this->assertStringContainsString('apple-touch-icon.png', $html);
        $this->assertStringContainsString('site.webmanifest', $html);
        $this->assertStringContainsString('images/og-cover.png', $html);
    }

    /** Aset brand (logo, ikon, cover OG) benar-benar ada dan valid. */
    public function test_brand_assets_exist_and_are_valid()
    {
        foreach ([
            'images/logo-mark.svg',
            'images/logo-horizontal.svg',
            'images/og-cover.png',
            'images/apple-touch-icon.png',
            'favicon.svg',
            'favicon.ico',
            'site.webmanifest',
        ] as $asset) {
            $this->assertFileExists(public_path($asset), "Aset brand {$asset} tidak ada.");
        }

        // PNG OG harus berukuran 1200x630 (standar kartu sosial).
        $cover = @getimagesize(public_path('images/og-cover.png'));
        $this->assertNotEmpty($cover, 'og-cover.png bukan gambar yang valid.');
        $this->assertSame(1200, $cover[0]);
        $this->assertSame(630, $cover[1]);

        // apple-touch-icon harus PNG persegi 180x180.
        $apple = @getimagesize(public_path('images/apple-touch-icon.png'));
        $this->assertNotEmpty($apple, 'apple-touch-icon.png bukan gambar yang valid.');
        $this->assertSame(180, $apple[0]);
        $this->assertSame(180, $apple[1]);

        // favicon.ico harus ICO multi-ukuran.
        $ico = file_get_contents(public_path('favicon.ico'));
        $this->assertSame("\x00\x00\x01\x00", substr($ico, 0, 4), 'favicon.ico bukan format ICO.');

        // Manifest web harus JSON valid dengan nama brand.
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);
        $this->assertIsArray($manifest);
        $this->assertSame('FastTopup Store', $manifest['name'] ?? null);
    }

    /** Kartu produk memakai partial bersama dan label jujur "Belum terjual". */
    public function test_product_card_shows_honest_sold_label()
    {
        $product = MarketplaceProduct::where('is_active', true)->firstOrFail();

        $this->get('/marketplace')
            ->assertOk()
            ->assertSee('product-subrow', false)
            ->assertSee('Belum terjual');
    }

    /** Tabel admin/katalog memakai header ber-scope dan kolom cari berlabel. */
    public function test_tables_and_search_fields_are_accessible()
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Header tabel: setiap <th> punya scope="col" agar pembaca layar
        // mengaitkan sel dengan kolomnya.
        $html = $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<th scope="col">Kode</th>', $html);
        $this->assertStringContainsString('aria-label="Cari transaksi"', $html);
    }

    /** Indikator progres tidak menyesatkan: langkah terakhir pesanan pending tetap "Selesai". */
    public function test_pending_order_step_label_is_not_misleading()
    {
        $this->assertSame('Selesai', \App\Support\OrderStatus::finalStepLabel('pending'));
        $this->assertSame('Selesai', \App\Support\OrderStatus::finalStepLabel('success'));
        $this->assertSame('Gagal', \App\Support\OrderStatus::finalStepLabel('failed'));
        $this->assertSame('Dibatalkan', \App\Support\OrderStatus::finalStepLabel('cancelled'));

        $buyer = $this->buyer();
        $product = \App\Models\Product::first();

        $transaction = \App\Models\Transaction::create([
            'transaction_code' => 'TXN-STEP-' . uniqid(),
            'user_id'          => $buyer->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);

        // Halaman sukses transaksi pending: langkah ketiga = "Selesai", bukan
        // "Gagal"/"Dibatalkan".
        $html = $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Selesai</div>', $html);
        $this->assertStringNotContainsString('>Dibatalkan</div>', $html);
    }

    /** Item navigasi/tab/chip yang aktif menandai dirinya untuk pembaca layar. */
    public function test_active_navigation_items_expose_aria_current()
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        // Nav admin: halaman aktif ditandai aria-current="page".
        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertOk()
            ->assertSee('aria-current="page"', false);

        // Chip filter status: item aktif ditandai aria-current="true".
        $buyer = $this->buyer();
        $this->actingAs($buyer)
            ->get(route('orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('aria-current="true"', false);
    }

    /** Guest tidak boleh melihat klaim "Pesanan Kamu" â€” cukup pratinjau katalog. */
    public function test_home_hero_shows_preview_for_guest_not_a_fake_order()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Pratinjau Pesanan', $html);
        $this->assertStringContainsString('Contoh', $html);
        $this->assertStringNotContainsString('Pesanan Kamu', $html);
        $this->assertStringNotContainsString('Pesanan Terakhir', $html);
    }

    /** Pengguna tanpa pesanan juga hanya melihat pratinjau, bukan pesanan orang lain. */
    public function test_home_hero_does_not_leak_other_users_orders()
    {
        $owner = $this->buyer();
        $other = $this->buyer();

        $game = \App\Models\GameTitle::first();
        $product = \App\Models\Product::where('game_title_id', $game->id)->first();

        \App\Models\Transaction::create([
            'transaction_code' => 'TXN-HERO-' . uniqid(),
            'user_id'          => $owner->id,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '11223344',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'success',
        ]);

        $this->actingAs($other)
            ->get('/')
            ->assertOk()
            ->assertSee('Pratinjau Pesanan')
            ->assertDontSee('Pesanan Terakhir');
    }

    /** Pengguna dengan pesanan nyata melihat pesanannya â€” dengan status yang jujur. */
    public function test_home_hero_shows_real_order_with_truthful_status()
    {
        $buyer = $this->buyer();
        $game = \App\Models\GameTitle::first();
        $product = \App\Models\Product::where('game_title_id', $game->id)->first();

        \App\Models\Transaction::create([
            'transaction_code' => 'TXN-HERO-' . uniqid(),
            'user_id'          => $buyer->id,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '55667788',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);

        $html = $this->actingAs($buyer)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Pesanan Terakhir', $html);
        $this->assertStringContainsString('Menunggu Konfirmasi', $html);
        // Pending tidak boleh diklaim "Selesai".
        $this->assertStringNotContainsString('chip-ok', $html);
        $this->assertStringContainsString($product->name, $html);
    }
}

