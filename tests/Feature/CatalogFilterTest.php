<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga perilaku pencarian/filter pada katalog (game & marketplace)
 * agar penempatan fitur tetap konsisten di kedua halaman.
 */
class CatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_topup_index_lists_all_games_by_default()
    {
        $response = $this->get(route('topup.index'));

        $response->assertOk();
        $response->assertViewHas('games', function ($games) {
            return $games->count() === GameTitle::where('is_active', true)->count();
        });
    }

    public function test_topup_index_filters_by_category()
    {
        $category = 'Battle Royale';
        $expected = GameTitle::where('is_active', true)->where('category', $category)->count();

        $response = $this->get(route('topup.index', ['kategori' => $category]));

        $response->assertOk();
        $response->assertViewHas('games', function ($games) use ($expected) {
            return $games->count() === $expected && $games->every(fn ($g) => $g->category === 'Battle Royale');
        });
    }

    public function test_topup_index_searches_by_name()
    {
        $response = $this->get(route('topup.index', ['q' => 'Mobile']));

        $response->assertOk();
        $response->assertViewHas('games', function ($games) {
            return $games->isNotEmpty() && $games->every(fn ($g) => stripos($g->name, 'Mobile') !== false);
        });
    }

    public function test_topup_index_banner_stats_ignore_active_filter()
    {
        $total = GameTitle::where('is_active', true)->count();

        $this->get(route('topup.index', ['kategori' => 'MOBA']))
            ->assertOk()
            ->assertViewHas('totalGames', $total);
    }

    public function test_topup_index_empty_state_when_no_match()
    {
        $this->get(route('topup.index', ['q' => 'zzz-tidak-ada']))
            ->assertOk()
            ->assertViewHas('games', fn ($games) => $games->isEmpty());
    }

    public function test_marketplace_index_filters_by_category_and_keeps_banner_total()
    {
        $total = MarketplaceProduct::where('is_active', true)->where('stock', '>', 0)->count();
        $category = MarketplaceProduct::where('is_active', true)
            ->whereNotNull('category')
            ->value('category');

        $this->get(route('marketplace.index', ['kategori' => $category]))
            ->assertOk()
            ->assertViewHas('products', fn ($products) => $products->every(fn ($p) => $p->category === $category))
            ->assertViewHas('totalProducts', $total);
    }
}
