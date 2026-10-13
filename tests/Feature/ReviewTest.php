<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Review;
use App\Models\User;
use App\Services\MarketplaceOrderService;
use App\Support\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Ulasan & rating produk marketplace.
 *
 * Prinsip yang diuji:
 *   1. Ulasan hanya lahir dari pesanan nyata berstatus Selesai, milik
 *      pengulas itu sendiri, dan produknya benar ada di pesanan tersebut.
 *   2. Satu akun = satu ulasan per produk (kiriman kedua memperbarui).
 *   3. Rating agregat dihitung dari ulasan nyata; produk tanpa ulasan
 *      berkata "Belum ada ulasan" — tidak pernah "0.0" karangan.
 *   4. Ulasan yang dasarnya dicabut (admin membatalkan konfirmasi) ikut dicabut.
 */
class ReviewTest extends TestCase
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

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function product(): MarketplaceProduct
    {
        return MarketplaceProduct::where('stock', '>', 5)->firstOrFail();
    }

    /**
     * Pesanan selesai milik satu pembeli untuk satu produk.
     */
    private function completedOrder(User $buyer, MarketplaceProduct $product, string $status = OrderStatus::SUCCESS): MarketplaceOrder
    {
        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-REV-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => $status,
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        return $order->fresh();
    }

    /* ---------------------------------------------------------------
     |  Batas kepercayaan
     * -------------------------------------------------------------- */

    /** Guest tidak bisa mengirim ulasan (harus login dulu). */
    public function test_guest_cannot_submit_review()
    {
        $order = $this->completedOrder($this->buyer(), $this->product());

        $this->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $order->items->first()->marketplace_product_id,
            'rating'                 => 5,
        ])->assertRedirect(route('login'));

        $this->assertSame(0, Review::count());
    }

    /** Pembeli dengan pesanan Selesai bisa mengulas, dan ulasannya tersimpan apa adanya. */
    public function test_buyer_with_completed_order_can_submit_review()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $product->id,
                'rating'                 => 5,
                'comment'                => 'Barangnya sesuai deskripsi, prosesnya cepat.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $review = Review::firstOrFail();

        $this->assertSame($buyer->id, $review->user_id);
        $this->assertSame($product->id, $review->marketplace_product_id);
        $this->assertSame($order->id, $review->marketplace_order_id);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Barangnya sesuai deskripsi, prosesnya cepat.', $review->comment);
    }

    /** Komentar opsional: rating saja tetap sah, komentar kosong disimpan sebagai null. */
    public function test_comment_is_optional()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $product->id,
                'rating'                 => 4,
                'comment'                => '   ',
            ])
            ->assertSessionHasNoErrors();

        $review = Review::firstOrFail();

        $this->assertSame(4, $review->rating);
        $this->assertNull($review->comment);
    }

    /** Pesanan yang belum Selesai tidak boleh diulas. */
    public function test_order_that_is_not_success_cannot_be_reviewed()
    {
        $buyer = $this->buyer();
        $product = $this->product();

        foreach ([OrderStatus::PENDING, OrderStatus::FAILED, OrderStatus::CANCELLED] as $status) {
            $order = $this->completedOrder($buyer, $product, $status);

            $this->actingAs($buyer)
                ->post(route('reviews.store', $order->order_code), [
                    'marketplace_product_id' => $product->id,
                    'rating'                 => 5,
                ])
                ->assertSessionHas('error');
        }

        $this->assertSame(0, Review::count());
    }

    /** Pembeli lain tidak boleh mengulas pesanan yang bukan miliknya. */
    public function test_other_buyer_cannot_review_someone_elses_order()
    {
        $owner = $this->buyer();
        $intruder = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($owner, $product);

        $this->actingAs($intruder)
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $product->id,
                'rating'                 => 1,
            ])
            ->assertForbidden();

        $this->assertSame(0, Review::count());
    }

    /** Produk yang tidak ada di pesanan itu tidak bisa diulas. */
    public function test_product_outside_the_order_cannot_be_reviewed()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $other = MarketplaceProduct::where('id', '!=', $product->id)->firstOrFail();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $other->id,
                'rating'                 => 5,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Review::count());
    }

    /** Akun admin tidak boleh mengulas (admin tidak pernah membeli). */
    public function test_admin_cannot_submit_review()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($this->admin())
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $product->id,
                'rating'                 => 5,
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, Review::count());
    }

    /* ---------------------------------------------------------------
     |  Validasi
     * -------------------------------------------------------------- */

    /** Rating wajib diisi dan hanya 1-5. */
    public function test_rating_must_be_between_one_and_five()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        foreach ([null, 0, 6, 'bintang'] as $invalid) {
            $payload = ['marketplace_product_id' => $product->id];

            if ($invalid !== null) {
                $payload['rating'] = $invalid;
            }

            $this->actingAs($buyer)
                ->post(route('reviews.store', $order->order_code), $payload)
                ->assertSessionHasErrors('rating');
        }

        $this->assertSame(0, Review::count());
    }

    /** Komentar dibatasi 1000 karakter supaya tidak jadi tempat spam. */
    public function test_comment_is_limited_in_length()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->post(route('reviews.store', $order->order_code), [
                'marketplace_product_id' => $product->id,
                'rating'                 => 5,
                'comment'                => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('comment');

        $this->assertSame(0, Review::count());
    }

    /* ---------------------------------------------------------------
     |  Satu ulasan per produk
     | -------------------------------------------------------------- */

    /** Kiriman kedua untuk produk yang sama memperbarui, bukan menggandakan. */
    public function test_second_submission_updates_instead_of_duplicating()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 2,
            'comment'                => 'Pertama.',
        ]);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
            'comment'                => 'Setelah dicoba lagi, ternyata bagus.',
        ])->assertSessionHas('success');

        $this->assertSame(1, Review::count());

        $review = Review::firstOrFail();

        $this->assertSame(5, $review->rating);
        $this->assertSame('Setelah dicoba lagi, ternyata bagus.', $review->comment);
    }

    /** Rata-rata dan jumlah dihitung dari ulasan nyata milik pembeli berbeda. */
    public function test_aggregate_is_computed_from_real_reviews()
    {
        $product = $this->product();

        $first = $this->buyer();
        $this->actingAs($first)->post(
            route('reviews.store', $this->completedOrder($first, $product)->order_code),
            ['marketplace_product_id' => $product->id, 'rating' => 5]
        );

        $second = $this->buyer();
        $this->actingAs($second)->post(
            route('reviews.store', $this->completedOrder($second, $product)->order_code),
            ['marketplace_product_id' => $product->id, 'rating' => 4]
        );

        $summary = $product->fresh()->ratingSummary();

        $this->assertSame(2, $summary['count']);
        $this->assertSame(4.5, $summary['average']);
    }

    /* ---------------------------------------------------------------
     |  Tampilan jujur
     | -------------------------------------------------------------- */

    /** Tanpa ulasan: "Belum ada ulasan", bukan angka 0.0 atau bintang kosong. */
    public function test_product_without_reviews_says_belum_ada_ulasan()
    {
        $product = $this->product();

        $html = $this->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Belum ada ulasan')
            ->getContent();

        $this->assertStringNotContainsString('ratingValue', $html, 'JSON-LD memuat rating tanpa ulasan nyata.');
        $this->assertStringNotContainsString('product-rating-value', $html, 'Angka rating muncul tanpa ulasan.');
        $this->assertStringNotContainsString('review-score', $html);
        $this->assertStringNotContainsString('0,0', $html);
        $this->assertStringNotContainsString('(0 ulasan)', $html);
        $this->assertStringNotContainsString('is-on', $html, 'Ada bintang terisi tanpa ulasan.');
    }

    /** Halaman produk menampilkan ulasan nyata beserta nama penyamaran penulisnya. */
    public function test_product_page_lists_real_reviews()
    {
        $buyer = $this->buyer();
        $buyer->update(['name' => 'Budi Santoso']);

        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
            'comment'                => 'Pengiriman instan, mantap.',
        ]);

        $this->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Budi S.')
            ->assertSee('Pengiriman instan, mantap.')
            ->assertSee('Pembelian terverifikasi')
            ->assertSee('5,0')
            ->assertSee('1 ulasan');
    }

    /** Kartu katalog menampilkan rating hanya bila ada ulasan. */
    public function test_catalog_card_shows_rating_only_when_reviews_exist()
    {
        $product = $this->product();

        $before = $this->get(route('marketplace.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('product-rating', $before);

        $buyer = $this->buyer();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 4,
        ]);

        $after = $this->get(route('marketplace.index'))->assertOk()->getContent();

        $this->assertStringContainsString('product-rating', $after);
        $this->assertStringContainsString('(1 ulasan)', $after);
    }

    /** Komentar berbahaya di-escape, tidak pernah dirender sebagai HTML. */
    public function test_review_comment_is_escaped()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
            'comment'                => '<script>alert("xss")</script>',
        ]);

        $html = $this->get(route('marketplace.show', $product->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** Data terstruktur memuat aggregateRating hanya setelah ada ulasan nyata. */
    public function test_structured_data_only_contains_rating_when_reviews_exist()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $html = $this->get(route('marketplace.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('"@type":"AggregateRating"', $html);
        $this->assertStringContainsString('"ratingValue":"5.0"', $html);
        $this->assertStringContainsString('"reviewCount":1', $html);
    }

    /** Formulir ulasan hanya muncul bagi pembeli yang punya pesanan Selesai. */
    public function test_review_form_is_only_shown_to_eligible_buyer()
    {
        $buyer = $this->buyer();
        $product = $this->product();

        // Belum pernah membeli -> tidak ada formulir.
        $this->actingAs($buyer)
            ->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertDontSee('Beri ulasan untuk produk ini');

        // Pesanan masih pending -> belum boleh mengulas.
        $this->completedOrder($buyer, $product, OrderStatus::PENDING);

        $this->actingAs($buyer)
            ->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertDontSee('Beri ulasan untuk produk ini');

        // Pesanan Selesai -> formulir muncul.
        $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Beri ulasan untuk produk ini');
    }

    /** Sudah mengulas -> formulir berubah jadi "Perbarui ulasanmu" dan terisi. */
    public function test_review_form_is_prefilled_for_existing_review()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 3,
            'comment'                => 'Lumayan.',
        ]);

        $this->actingAs($buyer)
            ->get(route('marketplace.show', $product->slug))
            ->assertOk()
            ->assertSee('Perbarui ulasanmu')
            ->assertSee('Lumayan.')
            ->assertSee('Perbarui Ulasan');
    }

    /** Halaman detail pesanan menyediakan pintu masuk ke formulir ulasan. */
    public function test_order_detail_links_to_review_form_when_success()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order->order_code))
            ->assertOk()
            ->assertSee('Ulasan Produk')
            ->assertSee('Belum diulas');

        // Setelah diulas, statusnya berubah menjadi "sudah terkirim".
        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order->order_code))
            ->assertOk()
            ->assertSee('Ulasanmu sudah terkirim');
    }

    /** Pesanan yang belum Selesai belum menawarkan ulasan. */
    public function test_order_detail_hides_review_section_until_success()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product, OrderStatus::PENDING);

        $this->actingAs($buyer)
            ->get(route('orders.show', $order->order_code))
            ->assertOk()
            ->assertSee('Ulasan bisa ditulis setelah pesanan ini berstatus Selesai.')
            ->assertDontSee('Belum diulas');
    }

    /* ---------------------------------------------------------------
     |  Konsistensi saat dasar ulasan dicabut
     | -------------------------------------------------------------- */

    /** Admin membatalkan konfirmasi -> ulasan dari pesanan itu ikut dicabut. */
    public function test_review_is_removed_when_confirmation_is_revoked()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $this->assertSame(1, Review::count());

        app(MarketplaceOrderService::class)->transitionTo($order->fresh(), OrderStatus::FAILED, false, [
            'source' => OrderStatus::SOURCE_ADMIN,
            'reason' => 'Konfirmasi salah.',
        ]);

        $this->assertSame(0, Review::count(), 'Ulasan harus dicabut saat konfirmasi dibatalkan.');
    }

    /** Ulasan dari pesanan lain milik pembeli yang sama tidak ikut terhapus. */
    public function test_revoking_one_order_keeps_reviews_from_other_orders()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $other = MarketplaceProduct::where('id', '!=', $product->id)->firstOrFail();

        $first = $this->completedOrder($buyer, $product);
        $second = $this->completedOrder($buyer, $other);

        $this->actingAs($buyer)->post(route('reviews.store', $first->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $this->actingAs($buyer)->post(route('reviews.store', $second->order_code), [
            'marketplace_product_id' => $other->id,
            'rating'                 => 4,
        ]);

        $this->assertSame(2, Review::count());

        app(MarketplaceOrderService::class)->transitionTo($first->fresh(), OrderStatus::FAILED, false, [
            'source' => OrderStatus::SOURCE_ADMIN,
            'reason' => 'Konfirmasi salah.',
        ]);

        $this->assertSame(1, Review::count());
        $this->assertSame($other->id, Review::first()->marketplace_product_id);
    }

    /* ---------------------------------------------------------------
     |  Model
     | -------------------------------------------------------------- */

    /** Nama penulis disamarkan agar halaman publik tidak membocorkan identitas. */
    public function test_reviewer_label_masks_the_full_name()
    {
        $buyer = $this->buyer();
        $buyer->update(['name' => 'Sultan Gaming']);
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $this->assertSame('Sultan G.', Review::firstOrFail()->reviewerLabel());
    }

    /** Aktivitas ulasan tercatat untuk jejak audit admin. */
    public function test_review_activity_is_logged()
    {
        $buyer = $this->buyer();
        $product = $this->product();
        $order = $this->completedOrder($buyer, $product);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 5,
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'review.create']);

        $this->actingAs($buyer)->post(route('reviews.store', $order->order_code), [
            'marketplace_product_id' => $product->id,
            'rating'                 => 3,
        ]);

        $this->assertDatabaseHas('activity_logs', ['action' => 'review.update']);
    }
}
