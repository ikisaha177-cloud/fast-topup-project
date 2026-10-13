<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Buat user admin, penjual, dan pembeli contoh.
     *
     * @return void
     */
    public function run()
    {
        // Admin utama (login: admin123@gmail.com / admin123)
        $admin = User::firstOrCreate(
            ['email' => 'admin123@gmail.com'],
            ['name' => 'Admin GantinoID', 'password' => Hash::make('admin123')]
        );
        $admin->role = 'admin';
        $admin->save();

        // Akun admin demo lama tidak lagi dipakai sebagai admin.
        User::where('email', 'admin@topupmarket.test')->update(['role' => 'user']);

        // Penjual (seller) - masih role user biasa
        $sellers = [
            ['name' => 'GameStore ID', 'email' => 'seller1@topupmarket.test'],
            ['name' => 'Sultan Gaming', 'email' => 'seller2@topupmarket.test'],
        ];

        foreach ($sellers as $seller) {
            User::firstOrCreate(
                ['email' => $seller['email']],
                ['name' => $seller['name'], 'password' => Hash::make('password')]
            );
        }

        // Pembeli contoh
        User::firstOrCreate(
            ['email' => 'user@topupmarket.test'],
            ['name' => 'User Demo', 'password' => Hash::make('password')]
        );
    }
}
