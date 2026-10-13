<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak kode promo di transaksi top up & pesanan marketplace.
 *
 * - promo_code      : kode yang dipakai (null bila tanpa promo).
 * - discount_amount : potongan rupiah; amount / total_amount di bawahnya sudah
 *                     berupa nilai SETELAH diskon, jadi laporan tetap jujur.
 */
class AddPromoToOrders extends Migration
{
    /** @var array<int, string> */
    private $tables = ['transactions', 'marketplace_orders'];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('promo_code')->nullable()->after('payment_method');
                $blueprint->unsignedBigInteger('discount_amount')->default(0)->after('promo_code');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['promo_code', 'discount_amount']);
            });
        }
    }
}
