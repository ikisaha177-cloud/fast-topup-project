<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Review;
use App\Models\User;
use App\Support\ActivityFeed;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P1 #6: feed "aktivitas terbaru" publik — hanya data nyata,
 * identitas pembeli disamarkan, waktu relatif bahasa Indonesia.
 */
class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function product(): MarketplaceProduct
    {
        return MarketplaceProduct::first();
    }

    private function makeSuccessOrder(User $buyer, string $code = ''): MarketplaceOrder
    {
        $product = $this->product();

        $order = MarketplaceOrder::create([
            'order_code'     => $code ?: 'MKT-FEED-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'success',
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        return $order;
    }

    private function makeReview(User $author, string $comment = 'Mantap'): Review
    {
        return Review::create([
            'marketplace_product_id' => $this->product()->id,
            'user_id'                => $author->id,
            'rating'                 => 5,
            'comment'                => $comment,
        ]);
    }

    public function test_feed_menampilkan_pembelian_sukses_dari_data_nyata()
    {
        $buyer = User::factory()->create(['name' => 'Siti Rahayu', 'role' => 'user']);
        $this->makeSuccessOrder($buyer);

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertSee('Aktivitas Terbaru', false)
            ->assertSee('Siti R.', false)
            ->assertSee('memesan ' . $this->product()->name, false);
    }

    public function test_feed_menampilkan_ulasan_dan_menyamarkan_nama()
    {
        $author = User::factory()->create(['name' => 'Budi Santoso', 'role' => 'user']);
        $this->makeReview($author, 'Barang sesuai');

        $response = $this->get(route('marketplace.index'));

        $response->assertOk()
            ->assertSee('Budi S.', false)
            ->assertSee('memberi ulasan 5 bintang untuk ' . $this->product()->name, false)
            ->assertDontSee('Budi Santoso', false);
    }

    public function test_feed_tidak_menampilkan_pesanan_non_sukses()
    {
        $buyer = User::factory()->create(['name' => 'Dewi Anggraini', 'role' => 'user']);

        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-FEED-PENDING',
            'user_id'        => $buyer->id,
            'total_amount'   => $this->product()->price,
            'payment_method' => 'QRIS',
            'status'         => 'pending',
        ]);

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertDontSee('Dewi A.', false)
            ->assertDontSee('Dewi Anggraini', false);

        $this->assertNotNull($order);
    }

    public function test_feed_kosong_tidak_menampilkan_blok_sama_sekali()
    {
        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertDontSee('Aktivitas Terbaru', false);
    }

    public function test_waktu_relatif_memakai_bahasa_indonesia()
    {
        $author = User::factory()->create(['name' => 'Andi Wijaya', 'role' => 'user']);
        $review = $this->makeReview($author);
        $review->created_at = Carbon::now()->subHours(3);
        $review->save();

        $items = ActivityFeed::items();

        $this->assertSame('3 jam lalu', $items[0]['time']);
        $this->assertStringNotContainsString('hours ago', $items[0]['time']);
    }

    public function test_item_feed_memakai_tautan_produk_dan_waktu_baru_saja()
    {
        $author = User::factory()->create(['name' => 'Rina', 'role' => 'user']);
        $this->makeReview($author);

        $items = ActivityFeed::items();

        $this->assertCount(1, $items);
        $this->assertSame('Rina', $items[0]['label']);
        $this->assertSame('Baru saja', $items[0]['time']);
        $this->assertSame(
            route('marketplace.show', $this->product()->slug),
            $items[0]['url']
        );
    }
}
