<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batas waktu pembayaran untuk transaksi top up & pesanan marketplace.
 *
 * `expires_at` diisi saat pesanan dibuat (created_at + jendela pembayaran).
 * Selama status masih `pending` dan waktu sudah lewat, pesanan dibatalkan
 * otomatis saat halamannya diakses (lazy expiry — lihat
 * {@see \App\Support\PaymentWindow} dan `expireIfDue()` pada kedua service).
 *
 * Kolomnya nullable dengan sengaja: baris lama (dibuat sebelum migrasi ini)
 * tidak punya batas waktu, jadi tidak pernah ikut kedaluwarsa.
 */
class AddExpiresAtToOrders extends Migration
{
    /** @var array<int, string> */
    private $tables = ['transactions', 'marketplace_orders'];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamp('expires_at')->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('expires_at');
            });
        }
    }
}
