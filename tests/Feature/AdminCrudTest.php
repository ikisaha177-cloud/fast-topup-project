<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cakupan menyeluruh untuk panel admin:
 * CRUD game / produk top up / produk marketplace, toggle, audit log,
 * pencarian & filter, serta penolakan akses untuk non-admin.
 */
class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(GameTitle $game, Product $product, User $user = null): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TXN-TEST-' . uniqid(),
            'user_id'          => $user ? $user->id : null,
            'game_title_id'    => $game->id,
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
            'order_code'     => 'MKT-TEST-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'pending',
        ]);

        MarketplaceOrderItem::create([
            'marketplace_order_id'  => $order->id,
            'marketplace_product_id' => $product->id,
            'quantity'              => 1,
            'price'                 => $product->price,
            'subtotal'              => $product->price,
        ]);

        return $order;
    }

    /* ---------------------------------------------------------------------
     | Game: create / update / delete / toggle
     * ------------------------------------------------------------------- */

    public function test_admin_can_create_game_and_it_is_logged_with_subject()
    {
        $this->actingAs($this->admin())
            ->post(route('admin.games.store'), [
                'name'      => 'Genshin Impact',
                'category'  => 'RPG',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.games.index'));

        $game = GameTitle::where('name', 'Genshin Impact')->first();

        $this->assertNotNull($game);
        $this->assertSame('genshin-impact', $game->slug);
        $this->assertTrue($game->is_active);

        $this->assertDatabaseHas('activity_logs', [
            'action'       => 'game.create',
            'subject_type' => GameTitle::class,
            'subject_id'   => $game->id,
        ]);
    }

    public function test_game_slug_is_made_unique_with_suffix()
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.games.store'), [
            'name' => 'Genshin Impact', 'category' => 'RPG', 'is_active' => '1',
        ]);

        $this->actingAs($admin)->post(route('admin.games.store'), [
            'name' => 'Genshin Impact', 'category' => 'RPG', 'is_active' => '1',
        ]);

        $this->assertDatabaseHas('game_titles', ['slug' => 'genshin-impact']);
        $this->assertDatabaseHas('game_titles', ['slug' => 'genshin-impact-1']);
    }

    public function test_admin_can_update_game_and_slug_follows_new_name()
    {
        $game = GameTitle::first();

        $this->actingAs($this->admin())
            ->put(route('admin.games.update', $game->id), [
                'name'      => 'Mobile Legends Bang Bang',
                'category'  => 'MOBA',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.games.index'));

        $game->refresh();

        $this->assertSame('Mobile Legends Bang Bang', $game->name);
        $this->assertSame('mobile-legends-bang-bang', $game->slug);
        $this->assertDatabaseHas('activity_logs', ['action' => 'game.update', 'subject_id' => $game->id]);
    }

    public function test_admin_can_delete_game_without_transactions()
    {
        $game = GameTitle::first();

        $this->actingAs($this->admin())
            ->delete(route('admin.games.destroy', $game->id))
            ->assertRedirect(route('admin.games.index'));

        $this->assertDatabaseMissing('game_titles', ['id' => $game->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'game.delete']);
    }

    public function test_game_with_transactions_cannot_be_deleted()
    {
        $game    = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->makeTransaction($game, $product, $this->buyer());

        $this->actingAs($this->admin())
            ->delete(route('admin.games.destroy', $game->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('game_titles', ['id' => $game->id]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'game.delete']);
    }

    public function test_admin_can_toggle_game_status()
    {
        $game = GameTitle::first();
        $before = (bool) $game->is_active;

        $this->actingAs($this->admin())
            ->post(route('admin.games.toggle', $game->id))
            ->assertRedirect();

        $this->assertSame(! $before, (bool) $game->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'game.toggle', 'subject_id' => $game->id]);
    }

    public function test_game_create_requires_name()
    {
        $before = GameTitle::count();

        $this->actingAs($this->admin())
            ->post(route('admin.games.store'), ['category' => 'RPG'])
            ->assertSessionHasErrors('name');

        $this->assertSame($before, GameTitle::count());
    }

    /* ---------------------------------------------------------------------
     | Produk top up: create / update / delete / toggle
     * ------------------------------------------------------------------- */

    public function test_admin_can_create_topup_product()
    {
        $game = GameTitle::first();

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), [
                'game_title_id' => $game->id,
                'name'          => '9999 Diamonds',
                'price'         => 1500000,
                'stock'         => 10,
                'is_active'     => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('name', '9999 Diamonds')->first();

        $this->assertNotNull($product);
        $this->assertSame(1500000, (int) $product->price);
        $this->assertDatabaseHas('activity_logs', [
            'action'       => 'product.create',
            'subject_type' => Product::class,
            'subject_id'   => $product->id,
        ]);
    }

    public function test_topup_product_requires_existing_game()
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), [
                'game_title_id' => 999999,
                'name'          => 'Produk Ngawur',
                'price'         => 1000,
                'stock'         => 1,
            ])
            ->assertSessionHasErrors('game_title_id');

        $this->assertDatabaseMissing('products', ['name' => 'Produk Ngawur']);
    }

    public function test_admin_can_update_topup_product()
    {
        $product = Product::first();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product->id), [
                'game_title_id' => $product->game_title_id,
                'name'          => 'Nominal Baru',
                'price'         => 99000,
                'stock'         => 42,
                'is_active'     => '1',
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('Nominal Baru', $product->name);
        $this->assertSame(99000, (int) $product->price);
        $this->assertSame(42, (int) $product->stock);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.update', 'subject_id' => $product->id]);
    }

    public function test_admin_can_delete_topup_product_without_transactions()
    {
        $product = Product::first();

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product->id))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.delete']);
    }

    public function test_topup_product_with_transactions_cannot_be_deleted()
    {
        $product = Product::first();
        $game    = $product->gameTitle;

        $this->makeTransaction($game, $product, $this->buyer());

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_can_toggle_topup_product()
    {
        $product = Product::first();
        $before = (bool) $product->is_active;

        $this->actingAs($this->admin())
            ->post(route('admin.products.toggle', $product->id))
            ->assertRedirect();

        $this->assertSame(! $before, (bool) $product->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'product.toggle']);
    }

    /* ---------------------------------------------------------------------
     | Produk marketplace: update / delete / toggle (+ upload gambar)
     * ------------------------------------------------------------------- */

    public function test_admin_can_update_marketplace_product_with_new_image()
    {
        Storage::fake('public');

        $product = MarketplaceProduct::first();

        $this->actingAs($this->admin())
            ->put(route('admin.marketplace.update', $product->id), [
                'name'        => 'Produk Marketplace Baru',
                'description' => 'Deskripsi baru',
                'price'       => 123000,
                'stock'       => 7,
                'category'    => 'Voucher',
                'image'       => \Illuminate\Http\UploadedFile::fake()->image('baru.png', 100, 100),
                'is_active'   => '1',
            ])
            ->assertRedirect(route('admin.marketplace.index'));

        $product->refresh();

        $this->assertSame('Produk Marketplace Baru', $product->name);
        $this->assertSame(7, (int) $product->stock);
        Storage::disk('public')->assertExists($product->image);
        $this->assertDatabaseHas('activity_logs', ['action' => 'marketplace.update', 'subject_id' => $product->id]);
    }

    public function test_admin_can_delete_marketplace_product_without_orders()
    {
        $product = MarketplaceProduct::first();

        $this->actingAs($this->admin())
            ->delete(route('admin.marketplace.destroy', $product->id))
            ->assertRedirect(route('admin.marketplace.index'));

        $this->assertDatabaseMissing('marketplace_products', ['id' => $product->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'marketplace.delete']);
    }

    public function test_marketplace_product_with_orders_cannot_be_deleted()
    {
        $product = MarketplaceProduct::first();

        $this->makeOrder($this->buyer(), $product);

        $this->actingAs($this->admin())
            ->delete(route('admin.marketplace.destroy', $product->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('marketplace_products', ['id' => $product->id]);
    }

    public function test_admin_can_toggle_marketplace_product()
    {
        $product = MarketplaceProduct::first();
        $before = (bool) $product->is_active;

        $this->actingAs($this->admin())
            ->post(route('admin.marketplace.toggle', $product->id))
            ->assertRedirect();

        $this->assertSame(! $before, (bool) $product->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'marketplace.toggle']);
    }

    /* ---------------------------------------------------------------------
     | Audit log
     * ------------------------------------------------------------------- */

    public function test_activity_log_records_action_description_and_subject()
    {
        $game = GameTitle::first();

        $this->actingAs($this->admin())->post(route('admin.games.toggle', $game->id));

        $log = ActivityLog::where('action', 'game.toggle')->first();

        $this->assertNotNull($log);
        $this->assertSame($this->admin()->id, $log->user_id);
        $this->assertStringContainsString($game->name, $log->description);
        $this->assertSame(GameTitle::class, $log->subject_type);
        $this->assertSame($game->id, (int) $log->subject_id);
    }

    public function test_activity_log_can_be_searched_by_action_and_description()
    {
        $admin = $this->admin();

        ActivityLog::create([
            'user_id'     => $admin->id,
            'action'      => 'game.create',
            'description' => 'Menambahkan game "Genshin Impact".',
        ]);
        ActivityLog::create([
            'user_id'     => $admin->id,
            'action'      => 'product.update',
            'description' => 'Memperbarui produk "Diamond".',
        ]);

        $byAction = $this->actingAs($admin)->get(route('admin.activity.index', ['q' => 'game.create']));
        $byAction->assertOk();
        $this->assertSame(['game.create'], $byAction->viewData('logs')->pluck('action')->all());

        $byDescription = $this->actingAs($admin)->get(route('admin.activity.index', ['q' => 'Diamond']));
        $byDescription->assertOk();
        $this->assertSame(['product.update'], $byDescription->viewData('logs')->pluck('action')->all());
    }

    public function test_activity_log_can_be_searched_by_user_name()
    {
        $admin = $this->admin();
        $other = User::factory()->create(['name' => 'Nama Unik Sekali']);

        ActivityLog::create(['user_id' => $admin->id, 'action' => 'a.one', 'description' => 'Milik admin']);
        ActivityLog::create(['user_id' => $other->id, 'action' => 'b.two', 'description' => 'Milik orang lain']);

        $response = $this->actingAs($admin)->get(route('admin.activity.index', ['q' => 'Nama Unik Sekali']));

        $response->assertOk();
        $this->assertSame(['b.two'], $response->viewData('logs')->pluck('action')->all());
    }

    public function test_activity_log_is_paginated_at_25_rows()
    {
        $admin = $this->admin();

        for ($i = 0; $i < 30; $i++) {
            ActivityLog::create([
                'user_id'     => $admin->id,
                'action'      => 'bulk.' . $i,
                'description' => 'Baris ' . $i,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.activity.index'));

        $response->assertOk();

        $logs = $response->viewData('logs');
        $this->assertSame(30, $logs->total());
        $this->assertSame(25, $logs->perPage());
        $this->assertCount(25, $logs->items());
    }

    /* ---------------------------------------------------------------------
     | Pencarian & filter daftar admin
     * ------------------------------------------------------------------- */

    public function test_games_index_can_be_searched()
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.games.index', ['q' => 'Mobile']));

        $response->assertOk();

        $names = $response->viewData('games')->pluck('name')->all();

        $this->assertContains('Mobile Legends', $names);
        $this->assertNotContains('Valorant', $names);
    }

    public function test_products_index_can_be_filtered_by_game()
    {
        $game = GameTitle::where('slug', 'valorant')->first();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.products.index', ['game' => $game->id]));

        $response->assertOk();

        $gameIds = $response->viewData('products')->pluck('game_title_id')->unique()->all();

        $this->assertSame([$game->id], $gameIds);
    }

    public function test_products_index_can_be_searched_by_name()
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.products.index', ['q' => 'Diamonds']));

        $response->assertOk();

        foreach ($response->viewData('products')->pluck('name') as $name) {
            $this->assertStringContainsString('Diamonds', $name);
        }
    }

    public function test_marketplace_index_can_be_searched()
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.marketplace.index', ['q' => 'Voucher']));

        $response->assertOk();

        $names = $response->viewData('products')->pluck('name')->all();

        $this->assertNotEmpty($names);

        foreach ($names as $name) {
            $this->assertStringContainsString('Voucher', $name);
        }
    }

    public function test_transactions_index_can_be_filtered_by_status()
    {
        $game    = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        $this->makeTransaction($game, $product, $this->buyer()); // pending

        $done = $this->makeTransaction($game, $product, $this->buyer());
        $done->update(['status' => 'success']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.transactions.index', ['status' => 'success']));

        $response->assertOk();
        $this->assertSame(['success'], $response->viewData('transactions')->pluck('status')->unique()->all());
    }

    public function test_orders_index_can_be_searched_by_code()
    {
        $order = $this->makeOrder($this->buyer(), MarketplaceProduct::first());

        $response = $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['q' => $order->order_code]));

        $response->assertOk();
        $this->assertSame([$order->order_code], $response->viewData('orders')->pluck('order_code')->all());
    }

    /* ---------------------------------------------------------------------
     | Kontrol akses: non-admin ditolak (403)
     * ------------------------------------------------------------------- */

    public function test_non_admin_is_denied_on_every_admin_route()
    {
        $buyer = $this->buyer();

        $game    = GameTitle::first();
        $product = Product::first();
        $mkt     = MarketplaceProduct::first();
        $txn     = $this->makeTransaction($game, $product, $buyer);
        $order   = $this->makeOrder($buyer, $mkt);

        $gets = [
            route('admin.dashboard'),
            route('admin.games.index'),
            route('admin.games.create'),
            route('admin.games.edit', $game->id),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product->id),
            route('admin.marketplace.index'),
            route('admin.marketplace.edit', $mkt->id),
            route('admin.transactions.index'),
            route('admin.orders.index'),
            route('admin.activity.index'),
        ];

        foreach ($gets as $url) {
            $this->actingAs($buyer)->get($url)->assertStatus(403);
        }

        $posts = [
            [route('admin.games.store'), ['name' => 'Hack', 'category' => 'X']],
            [route('admin.games.toggle', $game->id), []],
            [route('admin.products.store'), ['game_title_id' => $game->id, 'name' => 'Hack', 'price' => 1, 'stock' => 1]],
            [route('admin.products.toggle', $product->id), []],
            [route('admin.marketplace.toggle', $mkt->id), []],
            [route('admin.transactions.status', $txn->id), ['status' => 'success']],
            [route('admin.orders.status', $order->id), ['status' => 'success']],
        ];

        foreach ($posts as [$url, $payload]) {
            $this->actingAs($buyer)->post($url, $payload)->assertStatus(403);
        }

        $this->actingAs($buyer)->put(route('admin.games.update', $game->id), ['name' => 'Hack'])->assertStatus(403);
        $this->actingAs($buyer)->put(route('admin.products.update', $product->id), ['name' => 'Hack'])->assertStatus(403);
        $this->actingAs($buyer)->put(route('admin.marketplace.update', $mkt->id), ['name' => 'Hack'])->assertStatus(403);
        $this->actingAs($buyer)->delete(route('admin.games.destroy', $game->id))->assertStatus(403);
        $this->actingAs($buyer)->delete(route('admin.products.destroy', $product->id))->assertStatus(403);
        $this->actingAs($buyer)->delete(route('admin.marketplace.destroy', $mkt->id))->assertStatus(403);

        // Tidak ada perubahan status yang bocor.
        $this->assertSame('pending', $txn->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_guest_is_redirected_to_login_on_admin_routes()
    {
        $this->get(route('admin.games.index'))->assertRedirect(route('login'));
    }
}
