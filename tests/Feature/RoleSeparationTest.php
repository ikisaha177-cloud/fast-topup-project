<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function makeAdmin()
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin123@gmail.com'],
            ['name' => 'Admin TopUpMarket', 'password' => Hash::make('admin123')]
        );
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    private function makeBuyer()
    {
        return User::firstOrCreate(
            ['email' => 'user@topupmarket.test'],
            ['name' => 'User Demo', 'password' => Hash::make('password')]
        );
    }

    /** Admin login memakai kredensial baru dan diarahkan ke /admin. */
    public function test_admin_login_redirects_to_admin_dashboard()
    {
        $this->makeAdmin();

        $response = $this->post('/login', [
            'email'    => 'admin123@gmail.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    /** Admin tidak boleh membuka halaman top up. */
    public function test_admin_is_blocked_from_topup_routes()
    {
        $this->actingAs($this->makeAdmin())
            ->get('/top-up')
            ->assertRedirect(route('admin.dashboard'));
    }

    /** Admin tidak boleh membuka keranjang. */
    public function test_admin_is_blocked_from_cart()
    {
        $this->actingAs($this->makeAdmin())
            ->get('/cart')
            ->assertRedirect(route('admin.dashboard'));
    }

    /** Admin tidak boleh membuka checkout marketplace. */
    public function test_admin_is_blocked_from_marketplace_checkout()
    {
        $this->actingAs($this->makeAdmin())
            ->get('/marketplace/checkout')
            ->assertRedirect(route('admin.dashboard'));
    }

    /** Admin bisa membuka dashboard admin. */
    public function test_admin_can_open_dashboard()
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertStatus(200);
    }

    /** User biasa tidak boleh membuka halaman admin. */
    public function test_buyer_cannot_open_admin_dashboard()
    {
        $this->actingAs($this->makeBuyer())
            ->get('/admin')
            ->assertStatus(403);
    }

    /** User biasa masih bisa top up dan keranjang. */
    public function test_buyer_can_access_topup_and_cart()
    {
        $buyer = $this->makeBuyer();

        $this->actingAs($buyer)->get('/top-up')->assertStatus(200);
        $this->actingAs($buyer)->get('/cart')->assertStatus(200);
    }

    /** Register user baru tetap berjalan. */
    public function test_new_user_can_register()
    {
        $email = 'pendaftar_' . uniqid() . '@example.test';

        $response = $this->post('/register', [
            'name'                  => 'Pendaftar Baru',
            'email'                 => $email,
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => $email, 'role' => 'user']);
    }

    /** Semua halaman admin dapat dirender tanpa error blade. */
    public function test_admin_pages_render()
    {
        $admin = $this->makeAdmin();

        foreach ([
            '/',
            '/admin',
            '/admin/games',
            '/admin/games/create',
            '/admin/products',
            '/admin/products/create',
            '/admin/marketplace',
            '/admin/transactions',
            '/admin/orders',
            '/admin/activity',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertStatus(200);
        }
    }

    /** Halaman pembeli dapat dirender tanpa error blade. */
    public function test_buyer_pages_render()
    {
        $buyer = $this->makeBuyer();

        foreach ([
            '/',
            '/marketplace',
            '/transactions',
            '/profile',
        ] as $url) {
            $this->actingAs($buyer)->get($url)->assertStatus(200);
        }

        // Halaman guest (lupa password) hanya untuk yang belum login.
        auth()->logout();
        $this->get('/forgot-password')->assertStatus(200);

        $product = \App\Models\MarketplaceProduct::first();
        if ($product) {
            $this->actingAs($buyer)->get('/marketplace/' . $product->slug)->assertStatus(200);
        }
    }
}
