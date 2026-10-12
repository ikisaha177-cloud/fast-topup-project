<?php

namespace Database\Seeders;

use App\Models\GameTitle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameTitleSeeder extends Seeder
{
    /**
     * Seed the database with game titles and their products.
     *
     * @return void
     */
    public function run()
    {
        $games = [
            [
                'name' => 'Mobile Legends',
                'slug' => 'mobile-legends',
                'category' => 'MOBA',
                'icon' => 'images/games/mobile-legends.svg',
                'products' => [
                    ['name' => '86 Diamonds', 'description' => '86 Diamonds + 8 Bonus', 'price' => 22000],
                    ['name' => '172 Diamonds', 'description' => '172 Diamonds + 16 Bonus', 'price' => 44000],
                    ['name' => '257 Diamonds', 'description' => '257 Diamonds + 23 Bonus', 'price' => 65000],
                    ['name' => '706 Diamonds', 'description' => '706 Diamonds + 63 Bonus', 'price' => 175000],
                    ['name' => '1412 Diamonds', 'description' => '1412 Diamonds + 126 Bonus', 'price' => 350000],
                ],
            ],
            [
                'name' => 'Free Fire',
                'slug' => 'free-fire',
                'category' => 'Battle Royale',
                'icon' => 'images/games/free-fire.png',
                'products' => [
                    ['name' => '100 Diamonds', 'description' => '100 Diamonds', 'price' => 15000],
                    ['name' => '310 Diamonds', 'description' => '310 Diamonds', 'price' => 45000],
                    ['name' => '520 Diamonds', 'description' => '520 Diamonds', 'price' => 73000],
                    ['name' => '1060 Diamonds', 'description' => '1060 Diamonds', 'price' => 145000],
                    ['name' => '2180 Diamonds', 'description' => '2180 Diamonds', 'price' => 290000],
                ],
            ],
            [
                'name' => 'PUBG Mobile',
                'slug' => 'pubg-mobile',
                'category' => 'Battle Royale',
                'icon' => 'images/games/pubg-mobile.png',
                'products' => [
                    ['name' => '60 UC', 'description' => '60 Unknown Cash', 'price' => 15000],
                    ['name' => '325 UC', 'description' => '325 Unknown Cash', 'price' => 75000],
                    ['name' => '660 UC', 'description' => '660 Unknown Cash', 'price' => 149000],
                    ['name' => '1800 UC', 'description' => '1800 Unknown Cash', 'price' => 399000],
                    ['name' => '3850 UC', 'description' => '3850 Unknown Cash', 'price' => 799000],
                ],
            ],
            [
                'name' => 'Valorant',
                'slug' => 'valorant',
                'category' => 'FPS',
                'icon' => 'images/games/valorant.svg',
                'products' => [
                    ['name' => '475 Points', 'description' => '475 Valorant Points', 'price' => 50000],
                    ['name' => '1000 Points', 'description' => '1000 Valorant Points', 'price' => 100000],
                    ['name' => '2050 Points', 'description' => '2050 Valorant Points', 'price' => 200000],
                    ['name' => '5350 Points', 'description' => '5350 Valorant Points', 'price' => 500000],
                    ['name' => '11000 Points', 'description' => '11000 Valorant Points', 'price' => 1000000],
                ],
            ],
        ];

        foreach ($games as $game) {
            $title = GameTitle::updateOrCreate(
                ['slug' => $game['slug']],
                [
                    'name' => $game['name'],
                    'category' => $game['category'],
                    'icon' => $game['icon'],
                    'is_active' => true,
                ]
            );

            foreach ($game['products'] as $product) {
                $title->products()->updateOrCreate(
                    ['name' => $product['name']],
                    [
                        'description' => $product['description'],
                        'price' => $product['price'],
                        'stock' => 100,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
