<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentGatewayFields extends Migration
{
    /**
     * Menyimpan referensi & URL pembayaran dari gateway supaya halaman
     * pembayaran bisa menampilkan QR / tombol redirect yang benar.
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_provider');
            $table->string('payment_url', 512)->nullable()->after('payment_reference');
            $table->timestamp('paid_at')->nullable()->after('status');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_provider');
            $table->string('payment_url', 512)->nullable()->after('payment_reference');
            $table->timestamp('paid_at')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'payment_reference', 'payment_url', 'paid_at']);
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'payment_reference', 'payment_url', 'paid_at']);
        });
    }
}
