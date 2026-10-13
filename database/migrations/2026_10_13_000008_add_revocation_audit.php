<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit pembatalan konfirmasi (success -> failed).
 *
 * Kolom terpisah supaya informasi konfirmasi awal (confirmed_at/confirmed_by)
 * tetap utuh: pembatalan tidak menimpa siapa yang menyetujui, melainkan
 * mencatat siapa yang membatalkan dan kapan.
 */
class AddRevocationAudit extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('failure_reason');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('failure_reason');
            $table->unsignedBigInteger('revoked_by')->nullable()->after('revoked_at');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['revoked_at', 'revoked_by']);
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn(['revoked_at', 'revoked_by']);
        });
    }
}
