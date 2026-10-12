<?php

namespace Database\Seeders;

use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceProductSeeder extends Seeder
{
    /**
     * Isi data produk marketplace contoh.
     *
     * @return void
     */
    public function run()
    {
        $seller1 = User::where('email', 'seller1@topupmarket.test')->first();
        $seller2 = User::where('email', 'seller2@topupmarket.test')->first();

        if (! $seller1 || ! $seller2) {
            return;
        }

        $products = [
            ['seller' => $seller1, 'name' => 'Voucher Game Rp 50.000', 'category' => 'Voucher', 'price' => 48000, 'stock' => 25, 'image' => 'images/products/voucher-50k.svg', 'description' => 'Voucher saldo game senilai Rp 50.000.'],
            ['seller' => $seller1, 'name' => 'Voucher Game Rp 100.000', 'category' => 'Voucher', 'price' => 95000, 'stock' => 15, 'image' => 'images/products/voucher-100k.svg', 'description' => 'Voucher saldo game senilai Rp 100.000.'],
            ['seller' => $seller1, 'name' => 'Skin Epic Random', 'category' => 'Skin', 'price' => 75000, 'stock' => 20, 'image' => 'images/products/skin-epic.svg', 'description' => 'Skin kualitas epic acak untuk akun kamu.'],
            ['seller' => $seller1, 'name' => 'Skin Legend Random', 'category' => 'Skin', 'price' => 250000, 'stock' => 8, 'image' => 'images/products/skin-legend.svg', 'description' => 'Skin kualitas legend acak.'],
            ['seller' => $seller2, 'name' => 'Akun Sultan Level 80', 'category' => 'Akun', 'price' => 500000, 'stock' => 3, 'image' => 'images/products/akun-sultan.svg', 'description' => 'Akun game level tinggi dengan banyak koleksi.'],
            ['seller' => $seller2, 'name' => 'Akun Starter Level 30', 'category' => 'Akun', 'price' => 120000, 'stock' => 10, 'image' => 'images/products/akun-starter.svg', 'description' => 'Akun starter siap pakai untuk bermain.'],
            ['seller' => $seller2, 'name' => '1000 Koin In-Game', 'category' => 'Item', 'price' => 100000, 'stock' => 30, 'image' => 'images/products/koin.svg', 'description' => 'Paket 1000 koin untuk kebutuhan in-game.'],
            ['seller' => $seller2, 'name' => 'Battle Pass Season Ini', 'category' => 'Item', 'price' => 150000, 'stock' => 18, 'image' => 'images/products/battle-pass.svg', 'description' => 'Battle pass season berjalan.'],
        ];

        foreach ($products as $item) {
            MarketplaceProduct::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'user_id'     => $item['seller']->id,
                    'name'        => $item['name'],
                    'description' => $item['description'],
                    'price'       => $item['price'],
                    'stock'       => $item['stock'],
                    'category'    => $item['category'],
                    'image'       => $item['image'],
                    'is_active'   => true,
                ]
            );
        }
    }
}
