<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * Kode promo contoh untuk demo & pengembangan.
 *
 * Kode yang dipakai tes sengaja tidak disentuh di sini supaya dua dunia
 * (seeding demo vs assertion tes) tidak pernah bertabrakan.
 */
class PromoCodeSeeder extends Seeder
{
    public function run()
    {
        PromoCode::firstOrCreate(
            ['code' => 'WELCOME15'],
            [
                'description' => 'Sambutan pembeli baru',
                'type'        => 'percent',
                'value'       => 15,
                'min_spend'   => 20000,
                'max_discount' => 50000,
                'scope'       => 'all',
                'is_active'   => true,
            ]
        );

        PromoCode::firstOrCreate(
            ['code' => 'HEMAT5000'],
            [
                'description' => 'Potongan nominal untuk top up apa pun',
                'type'        => 'fixed',
                'value'       => 5000,
                'min_spend'   => 30000,
                'scope'       => 'all',
                'is_active'   => true,
            ]
        );
    }
}
