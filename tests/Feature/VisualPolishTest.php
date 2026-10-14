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
use App\Support\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Efek visual "bintang jatuh" + kartu tilt 3D, dan penyempurnaan sistem
 * pendukung (email pengirim transaksional).
 *
 * Yang dijaga:
 *   - Latar bintang dirender dengan struktur & variabel CSS yang benar.
 *   - Arah lintasan bintang jatuh selalu sama dengan garis cahayanya
 *     (bukan "nyerong"), lewat sudut per-bintang + gerak di sumbu lokal.
 *   - Logo game memakai dimensi intrinsik sesuai kotaknya (anti CLS).
 *   - Alamat email transaksional tidak pernah kosong.
 *   - Nilai desimal tidak bocor ke HTML publik (mis. "4.9").
 */
class VisualPolishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    /* ---------------------------------------------------------------
     |  Latar bintang (hero & banner)
     * -------------------------------------------------------------- */

    /** Hero memuat struktur bintang: lapisan kedalaman, debu, dan streak. */
    public function test_home_renders_starfield_structure()
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('class="starfield"', $html);
        $this->assertStringContainsString('starfield-layer', $html);
        $this->assertStringContainsString('sf-dust', $html);
        $this->assertStringContainsString('sf-shoot', $html);

        // Tiga lapisan kedalaman untuk efek parallax.
        $this->assertSame(3, substr_count($html, 'class="starfield-layer"'));
    }

    /** Setiap streak membawa sudut & jarak lintasan sendiri. */
    public function test_shooting_stars_carry_angle_and_distance_variables()
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('--angle:', $html);
        $this->assertStringContainsString('--dist:', $html);
        $this->assertStringContainsString('--len:', $html);

        // Sudut memakai satuan derajat (dipakai untuk memutar pembungkus).
        $this->assertMatchesRegularExpression('/--angle:\d+deg/', $html);
    }

    /** Halaman dalam memakai versi bintang yang lebih ringkas. */
    public function test_inner_banners_use_compact_starfield()
    {
        foreach ([route('topup.index'), route('marketplace.index'), route('page.winrate')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('starfield-compact', $html);
            $this->assertStringContainsString('sf-shoot', $html);
        }
    }

    /** Bintang bersifat dekoratif: tidak boleh mencuri fokus pembaca layar. */
    public function test_starfield_is_decorative_for_assistive_tech()
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="starfield"[^>]*aria-hidden="true"/', $html);
    }

    /* ---------------------------------------------------------------
     |  Kartu 3D
     * -------------------------------------------------------------- */

    /** Kartu katalog memakai penanda tilt agar JS bisa menambahkan kedalaman. */
    public function test_catalog_cards_are_tilt_enabled()
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('data-tilt', $html);
    }

    /* ---------------------------------------------------------------
     |  Penyempurnaan sistem
     * -------------------------------------------------------------- */

    /** Logo game memakai dimensi intrinsik sesuai ukuran kotaknya. */
    public function test_game_logo_dimensions_match_their_box()
    {
        // Kartu katalog besar (banner): kotak 8:5.
        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('width="480" height="300"', $home);

        // Halaman detail memakai logo kecil: dimensi kotak 1:1.
        $game = GameTitle::where('is_active', true)->first();
        $detail = $this->get(route('topup.show', $game->slug))->assertOk()->getContent();
        $this->assertStringContainsString('width="76" height="76"', $detail);
    }

    /** Email transaksional punya pengirim yang selalu terisi. */
    public function test_transactional_from_address_is_never_empty()
    {
        config(['store.support_email' => '', 'store.email' => 'cs@example.test']);

        $this->assertSame('cs@example.test', Contact::supportEmail());

        config(['store.support_email' => 'noreply@example.test']);

        $this->assertSame('noreply@example.test', Contact::supportEmail());
    }

    /** Notifikasi memakai alamat pengirim dari config, bukan bawaan framework. */
    public function test_notifications_set_from_address_from_config()
    {
        config(['store.support_email' => 'noreply@example.test']);

        $user = User::factory()->create();

        $welcome = (new WelcomeNotification())->toMail($user);
        $this->assertSame(['noreply@example.test', 'FastTopup Store'], $welcome->from);

        $transaction = $this->makeTransaction($user);
        $notif = (new TransactionStatusChanged($transaction, 'Pembayaran diterima'))->toMail($user);
        $this->assertSame('noreply@example.test', $notif->from[0]);

        $order = $this->makeOrder($user);
        $orderNotif = (new OrderStatusChanged($order, 'Pembayaran diterima'))->toMail($user);
        $this->assertSame('noreply@example.test', $orderNotif->from[0]);
    }

    /** Nilai desimal tidak boleh bocor ke gaya animasi bintang. */
    public function test_no_stray_decimal_placeholders_leak_to_markup()
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('4.9', $html);

        // Durasi/delay bintang harus bulat (tanpa desimal) di atribut style.
        $this->assertDoesNotMatchRegularExpression(
            '/animation-(?:delay|duration):\d+\.\d+s/',
            $html
        );
    }

    /* ---------------------------------------------------------------
     |  Helper
     * -------------------------------------------------------------- */

    private function makeTransaction(User $user): Transaction
    {
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        return Transaction::create([
            'user_id'           => $user->id,
            'game_title_id'     => $game->id,
            'product_id'        => $product->id,
            'user_game_id'      => '12345678',
            'transaction_code'  => 'TRX-VISUAL-0001',
            'amount'            => $product->price,
            'payment_method'    => 'QRIS',
            'status'            => 'pending',
        ]);
    }

    private function makeOrder(User $user): \App\Models\MarketplaceOrder
    {
        $product = MarketplaceProduct::where('stock', '>', 0)->first();

        return \App\Models\MarketplaceOrder::create([
            'user_id'       => $user->id,
            'order_code'    => 'ORD-VISUAL-0001',
            'total_amount'  => $product->price,
            'status'        => 'pending',
            'payment_method' => 'QRIS',
        ]);
    }
}

