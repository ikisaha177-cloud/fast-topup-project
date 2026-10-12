<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageAssetsVerifyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_public_pages_render_without_letter_placeholder()
    {
        foreach (['/', '/top-up', '/marketplace'] as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $html = $response->getContent();
            $this->assertStringNotContainsString('background:#2f6bff; color:#fff; font-size:32px', $html);
            $this->assertStringNotContainsString('class="game-icon"', $html);
        }
    }

    public function test_seeded_games_have_existing_icon_files()
    {
        foreach (GameTitle::all() as $game) {
            if (! $game->icon) {
                continue;
            }
            $this->assertFileExists(public_path($game->icon), "Icon {$game->icon} tidak ada.");

            $this->get('/top-up/' . $game->slug)
                ->assertStatus(200)
                ->assertSee(asset($game->icon), false)
                ->assertSee('class="game-logo', false);
        }
    }

    public function test_seeded_marketplace_products_have_existing_image_files()
    {
        foreach (MarketplaceProduct::all() as $product) {
            if (! $product->image) {
                continue;
            }
            $this->assertFileExists(public_path($product->image), "Image {$product->image} tidak ada.");

            $this->get('/marketplace/' . $product->slug)
                ->assertStatus(200)
                ->assertSee(asset($product->image), false);
        }
    }

    public function test_known_assets_are_present()
    {
        foreach ([
            'images/games/mobile-legends.svg',
            'images/games/free-fire.png',
            'images/games/pubg-mobile.png',
            'images/games/valorant.svg',
            'images/games/placeholder-game.svg',
            'images/products/voucher-50k.svg',
            'images/products/voucher-100k.svg',
            'images/products/skin-epic.svg',
            'images/products/skin-legend.svg',
            'images/products/placeholder-product.svg',
        ] as $asset) {
            $this->assertFileExists(public_path($asset), "Asset {$asset} tidak ada.");
        }
    }
}
