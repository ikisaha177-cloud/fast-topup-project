<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti pembayaran yang diunggah pembeli saat menandai "Saya Sudah Bayar".
 *
 * Bersifat opsional: alur lama (tanpa unggah) tetap berjalan. Bila ada,
 * admin dapat melihat gambarnya di halaman verifikasi sebelum memutuskan.
 */
class AddPaymentProof extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_claimed_at');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_claimed_at');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('payment_proof');
        });
    }
}
