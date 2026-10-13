<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Halaman statis (kebijakan & bantuan) dan sitemap.xml.
 *
 * Memastikan halaman publik dapat diakses, memuat tautan silang yang benar,
 * dan sitemap valid sekaligus menyertakan halaman katalog.
 */
class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    /** Tiga halaman kebijakan tampil dengan judul yang tepat. */
    public function test_policy_pages_render()
    {
        $this->get(route('page.how-to-order'))
            ->assertOk()
            ->assertSee('Cara Order');

        $this->get(route('page.terms'))
            ->assertOk()
            ->assertSee('Syarat &amp; Ketentuan', false);

        $this->get(route('page.privacy'))
            ->assertOk()
            ->assertSee('Kebijakan Privasi');
    }

    /** Setiap halaman kebijakan menautkan ke dua halaman lainnya. */
    public function test_policy_pages_cross_link()
    {
        $html = $this->get(route('page.terms'))->assertOk()->getContent();

        $this->assertStringContainsString(route('page.how-to-order'), $html);
        $this->assertStringContainsString(route('page.privacy'), $html);
    }

    /** Halaman kebijakan bisa diakses tanpa login (publik). */
    public function test_policy_pages_are_public()
    {
        foreach (['page.how-to-order', 'page.terms', 'page.privacy'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    /** Footer menyediakan tautan ke halaman kebijakan. */
    public function test_footer_links_to_policy_pages()
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('page.terms'), $html);
        $this->assertStringContainsString(route('page.privacy'), $html);
        $this->assertStringContainsString(route('page.how-to-order'), $html);
    }

    /** Sitemap berformat XML dan memuat halaman utama serta katalog. */
    public function test_sitemap_is_valid_xml_and_lists_public_urls()
    {
        $response = $this->get(route('sitemap'))->assertOk();

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));

        $body = $response->getContent();

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $body);
        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringContainsString(route('home'), $body);
        $this->assertStringContainsString(route('topup.index'), $body);
        $this->assertStringContainsString(route('marketplace.index'), $body);

        // XML harus benar-benar dapat diparse.
        $xml = @simplexml_load_string($body);
        $this->assertNotFalse($xml, 'Sitemap bukan XML yang valid.');
        $this->assertGreaterThan(0, count($xml->url));
    }

    /** Sitemap memuat halaman detail game aktif & produk marketplace berstok. */
    public function test_sitemap_lists_catalog_details()
    {
        $game = GameTitle::where('is_active', true)->first();
        $product = MarketplaceProduct::where('is_active', true)->where('stock', '>', 0)->first();

        $body = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringContainsString(route('topup.show', $game->slug), $body);
        $this->assertStringContainsString(route('marketplace.show', $product->slug), $body);
    }

    /** Sitemap tidak membocorkan halaman privat (admin, keranjang, transaksi). */
    public function test_sitemap_excludes_private_pages()
    {
        $body = $this->get(route('sitemap'))->assertOk()->getContent();

        $this->assertStringNotContainsString('/admin', $body);
        $this->assertStringNotContainsString('/cart', $body);
        $this->assertStringNotContainsString('/transactions', $body);
        $this->assertStringNotContainsString('/orders', $body);
    }
}
