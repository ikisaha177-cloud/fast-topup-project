<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Pengerasan untuk produksi (paket A + prioritas B).
 *
 * Yang dijaga di sini:
 *   - Header keamanan terkirim (anti clickjacking, sniffing, referrer).
 *   - Health check /up untuk uptime monitor.
 *   - Halaman privat (invoice) tidak diindeks mesin pencari.
 *   - Aset memakai cache busting (?v=).
 *   - Canonical URL tersedia.
 *   - Command payments:expire menyapu pesanan kedaluwarsa tanpa pengunjung.
 *   - Rate limit aktif (429) dan halaman 429/503 tersedia.
 *   - Alur verifikasi email berjalan dengan tautan bertanda tangan.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed();
    }

    /* ---------------------------------------------------------------
     |  Header keamanan
     * -------------------------------------------------------------- */

    /** Setiap respons toko membawa header keamanan dasar. */
    public function test_security_headers_are_present()
    {
        $response = $this->get(route('home'))->assertOk();

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    /** Header keamanan juga berlaku di halaman portal pembayaran. */
    public function test_security_headers_on_payment_portal()
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $transaction = $this->makeTransaction($buyer);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    /* ---------------------------------------------------------------
     |  Health check
     * -------------------------------------------------------------- */

    /** Endpoint /up mengembalikan 200 untuk monitor uptime. */
    public function test_health_endpoint_returns_ok()
    {
        $this->get('/up')
            ->assertOk()
            ->assertSee('OK');
    }

    /* ---------------------------------------------------------------
     |  SEO: noindex, canonical, cache busting
     * -------------------------------------------------------------- */

    /** Invoice berisi data pribadi: tidak boleh diindeks. */
    public function test_invoice_is_noindex()
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $transaction = $this->makeTransaction($buyer);

        $this->actingAs($buyer)
            ->get(route('invoice.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    /** Stylesheet memakai query versi agar cache lama tidak nyangkut. */
    public function test_stylesheet_has_cache_busting_version()
    {
        config(['app.asset_version' => '7']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('css/style.css?v=7', false);
    }

    /** Halaman publik menyertakan canonical URL. */
    public function test_pages_include_canonical_url()
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="canonical"', false);
    }

    /* ---------------------------------------------------------------
     |  Scheduler: pembatalan otomatis tanpa pengunjung
     * -------------------------------------------------------------- */

    /** Command terjadwal membatalkan transaksi & pesanan yang kedaluwarsa. */
    public function test_scheduled_command_expires_overdue_transactions()
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $transaction = $this->makeTransaction($buyer, ['expires_at' => now()->subMinutes(10)]);

        $this->artisan('payments:expire')->assertExitCode(0);

        $this->assertSame(OrderStatus::CANCELLED, $transaction->fresh()->status);
        $this->assertTrue($transaction->fresh()->wasCancelledByExpiry());
    }

    /** Command tidak menyentuh pesanan yang masih dalam batas waktu. */
    public function test_scheduled_command_leaves_fresh_orders_alone()
    {
        $buyer = User::factory()->create(['role' => 'user']);
        $transaction = $this->makeTransaction($buyer, ['expires_at' => now()->addHour()]);

        $this->artisan('payments:expire')->assertExitCode(0);

        $this->assertSame(OrderStatus::PENDING, $transaction->fresh()->status);
    }

    /* ---------------------------------------------------------------
     |  Rate limit & halaman error
     * -------------------------------------------------------------- */

    /** Percobaan login beruntun dibatasi (429), bukan dibiarkan tanpa batas. */
    public function test_login_is_rate_limited()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login'), [
                'email'    => 'salah@example.test',
                'password' => 'salahsekali',
            ]);
        }

        $this->post(route('login'), [
            'email'    => 'salah@example.test',
            'password' => 'salahsekali',
        ])->assertStatus(429);
    }

    /** Halaman error 429 & 503 tersedia dan memakai layout toko. */
    public function test_error_views_exist()
    {
        $this->assertTrue(view()->exists('errors.429'));
        $this->assertTrue(view()->exists('errors.503'));

        $this->assertStringContainsString(
            'Terlalu Banyak Permintaan',
            view('errors.429')->render()
        );
        $this->assertStringContainsString(
            'Sedang Pemeliharaan',
            view('errors.503')->render()
        );
    }

    /* ---------------------------------------------------------------
     |  Verifikasi email
     * -------------------------------------------------------------- */

    /** Tautan verifikasi bertanda tangan menandai email sebagai terverifikasi. */
    public function test_signed_verification_link_verifies_email()
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);
        $this->assertNull($user->email_verified_at);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('home'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** Tautan tanpa tanda tangan ditolak. */
    public function test_unsigned_verification_link_is_rejected()
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertStatus(403);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /** Halaman pemberitahuan verifikasi memuat tombol kirim ulang. */
    public function test_verification_notice_page_shows_resend_button()
    {
        $user = User::factory()->unverified()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Kirim Ulang Tautan Verifikasi');
    }

    /* ---------------------------------------------------------------
     |  Bantuan
     * -------------------------------------------------------------- */

    /**
     * Transaksi pending milik pembeli, siap dipakai tes.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makeTransaction(User $user, array $overrides = []): Transaction
    {
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->firstOrFail();

        return Transaction::create(array_merge([
            'transaction_code' => 'TXN-HARD-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => OrderStatus::PENDING,
            'expires_at'       => now()->addHour(),
        ], $overrides));
    }
}
