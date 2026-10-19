<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\Product;
use App\Support\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreHelpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_halaman_top_up_menampilkan_faq_game_terstruktur()
    {
        $response = $this->get('/top-up/mobile-legends');

        $response->assertOk()
            ->assertSee('Pertanyaan umum seputar Mobile Legends', false)
            ->assertSee('Berapa lama diamond masuk setelah bayar?', false)
            ->assertSee('User ID saya ada di mana?', false)
            ->assertSee('Di mana menemukan User ID?', false)
            ->assertSee('Buka profil akun di dalam game; User ID tertera di bawah nama akun, biasanya 8-10 digit angka.', false)
            ->assertSee('data-selection-name', false)
            ->assertSee('data-selection-price', false)
            ->assertSee('Lanjut ke Pembayaran', false);
    }

    public function test_id_guide_and_live_nominal_summary_render_for_topup_game()
    {
        $this->get('/top-up/mobile-legends')
            ->assertOk()
            ->assertSee('Di mana menemukan User ID?', false)
            ->assertSee('Buka profil akun di dalam game; User ID tertera di bawah nama akun, biasanya 8-10 digit angka.', false)
            ->assertSee('Jangan pernah berikan password akun.', false)
            ->assertSee('data-selection-name', false)
            ->assertSee('data-selection-price', false)
            ->assertSee('Total Rp', false);
    }

    public function test_game_tanpa_faq_tidak_menampilkan_blok_faq()
    {
        $game = GameTitle::create([
            'name' => 'Tanpa FAQ',
            'slug' => 'tanpa-faq',
            'is_active' => true,
        ]);

        Product::create([
            'game_title_id' => $game->id,
            'name' => '100 Coins',
            'description' => '100 Coins',
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        $this->get('/top-up/tanpa-faq')
            ->assertOk()
            ->assertDontSee('Pertanyaan umum seputar', false);
    }

    public function test_tombol_whatsapp_memakai_nomor_dari_config()
    {
        config(['store.whatsapp' => '6281111222333']);

        $this->get('/top-up/mobile-legends')
            ->assertOk()
            ->assertSee('wa.me/6281111222333', false);
    }

    public function test_footer_menampilkan_tautan_whatsapp_cs()
    {
        config(['store.whatsapp' => '6281111222333']);

        $this->get('/')->assertSee('Chat WhatsApp CS', false)
            ->assertSee('wa.me/6281111222333', false);
    }

    public function test_tombol_whatsapp_disembunyikan_bila_nomor_kosong()
    {
        config(['store.whatsapp' => '']);

        $this->get('/top-up/mobile-legends')
            ->assertOk()
            ->assertDontSee('wa.me/', false);

        $this->get('/')->assertOk()->assertDontSee('wa.me/', false);
    }

    public function test_nomor_whatsapp_dinormalisasi()
    {
        config(['store.whatsapp' => '+62 812-3456-7890']);

        $this->assertSame('6281234567890', Contact::whatsappNumber());
        $this->assertStringContainsString('wa.me/6281234567890', Contact::whatsappUrl('halo'));
    }

    public function test_faq_korup_dibuang_sebelum_ditampilkan()
    {
        $game = GameTitle::create([
            'name' => 'Korup',
            'slug' => 'faq-korup',
            'is_active' => true,
            'faqs' => [
                ['question' => '<b>Tanya</b>', 'answer' => 'Jawab <script>alert(1)</script>'],
                ['question' => '', 'answer' => 'tanya kosong'],
                'bukan-array',
                ['question' => 'tanpa jawab', 'answer' => ''],
            ],
        ]);

        $items = $game->faqItems();

        $this->assertCount(1, $items);
        $this->assertSame('Tanya', $items[0]['question']);
        // strip_tags menyisakan teks di dalam tag; hasil akhir tetap aman
        // karena dirender lewat Blade {{ }} yang selalu meng-escape.
        $this->assertSame('Jawab alert(1)', $items[0]['answer']);
    }
}
