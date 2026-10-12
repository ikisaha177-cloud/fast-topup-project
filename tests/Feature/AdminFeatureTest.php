<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin()
    {
        return User::where('role', 'admin')->first();
    }

    /** Admin bisa menambah game baru + tercatat di log. */
    public function test_admin_can_create_game_and_log_activity()
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.games.store'), [
                'name'      => 'Genshin Impact',
                'category'  => 'RPG',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.games.index'));

        $this->assertDatabaseHas('game_titles', ['name' => 'Genshin Impact', 'slug' => 'genshin-impact']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'game.create']);
    }

    /** Admin bisa upload icon game. */
    public function test_admin_can_upload_game_icon()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = UploadedFile::fake()->image('icon.png', 120, 120);

        $this->actingAs($this->admin())
            ->post(route('admin.games.store'), [
                'name'      => 'Honkai Star Rail',
                'category'  => 'RPG',
                'icon'      => $file,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.games.index'));

        $game = GameTitle::where('name', 'Honkai Star Rail')->first();

        $this->assertNotNull($game->icon);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($game->icon);
    }

    /** Admin bisa menambah produk top up. */
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

        $this->assertDatabaseHas('products', ['name' => '9999 Diamonds']);
    }

    /** Ubah status transaksi pending -> cancelled mengembalikan stock. */
    public function test_admin_cancelling_transaction_restores_stock()
    {
        Notification::fake();

        $user = User::factory()->create();
        $game = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();
        $stockBefore = $product->stock;

        $this->actingAs($user)->post(route('topup.checkout.store'), [
            'game_slug'    => $game->slug,
            'user_game_id' => '12345678',
            'product_id'   => $product->id,
        ]);
        $this->actingAs($user)->post(route('topup.pay'), ['payment_method' => 'QRIS']);

        $transaction = Transaction::first();
        $this->assertSame($stockBefore - 1, $product->fresh()->stock);

        $this->actingAs($this->admin())
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $transaction->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock);
        $this->assertDatabaseHas('activity_logs', ['action' => 'transaction.status']);
    }

    /** Ubah status pesanan marketplace mengembalikan stock tiap item. */
    public function test_admin_cancelling_order_restores_item_stock()
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = MarketplaceProduct::where('stock', '>', 0)->first();
        $stockBefore = $product->stock;

        $this->actingAs($user)->post(route('marketplace.add-to-cart', $product->slug), ['quantity' => 2]);
        $this->actingAs($user)->post(route('marketplace.checkout.process'), ['payment_method' => 'QRIS']);

        $order = MarketplaceOrder::first();
        $this->assertSame($stockBefore - 2, $product->fresh()->stock);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.status', $order->id), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame($stockBefore, $product->fresh()->stock);
    }

    /** Halaman log aktivitas dapat dibuka admin. */
    public function test_admin_activity_page_renders()
    {
        ActivityLog::create([
            'user_id'     => $this->admin()->id,
            'action'      => 'test.action',
            'description' => 'Aktivitas uji',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('Aktivitas uji');
    }

    /** Buyer tidak boleh membuka log aktivitas. */
    public function test_buyer_cannot_open_activity_log()
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('admin.activity.index'))
            ->assertStatus(403);
    }
}
