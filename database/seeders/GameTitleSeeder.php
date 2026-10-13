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
                'faqs' => [
                    ['question' => 'Berapa lama diamond masuk setelah bayar?', 'answer' => 'Umumnya 1-5 menit setelah pembayaran terverifikasi. Bila lewat 30 menit belum masuk, hubungi CS dengan nomor pesananmu.'],
                    ['question' => 'Data apa yang dibutuhkan untuk top up?', 'answer' => 'Cukup User ID (dan Server ID bila game memintanya). Kami tidak pernah meminta password akunmu.'],
                    ['question' => 'User ID saya ada di mana?', 'answer' => 'Buka profil akun di dalam game; User ID tertera di bawah nama akun, biasanya 8-10 digit angka.'],
                ],
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
                'faqs' => [
                    ['question' => 'Berapa lama diamond Free Fire masuk?', 'answer' => 'Setelah pembayaran terverifikasi, diamond umumnya masuk dalam 1-5 menit ke akun dengan ID yang kamu isi.'],
                    ['question' => 'Di mana melihat ID akun Free Fire?', 'answer' => 'Ketuk avatar profil di lobby; ID akun tertera di bawah nama, contohnya 1234567890.'],
                ],
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
                'faqs' => [
                    ['question' => 'UC masuk ke akun region mana?', 'answer' => 'UC masuk ke akun sesuai region akunmu saat ini. Kami tidak memindahkan UC antar region, jadi pastikan region benar sebelum memesan.'],
                    ['question' => 'Berapa lama proses top up UC?', 'answer' => 'Umumnya 1-5 menit setelah pembayaran terverifikasi. Simpan nomor pesanan bila butuh pengecekan lanjutan.'],
                ],
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
                'faqs' => [
                    ['question' => 'Points masuk ke region akun yang mana?', 'answer' => 'Points masuk ke akun Riot sesuai region akunmu. Kami tidak memindahkan antar region, sesuaikan region sebelum checkout.'],
                    ['question' => 'Berapa lama Valorant Points masuk?', 'answer' => 'Umumnya 1-5 menit setelah pembayaran terverifikasi. Bila lebih lama, hubungi CS dengan nomor pesananmu.'],
                ],
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
                    'faqs' => $game['faqs'] ?? null,
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
