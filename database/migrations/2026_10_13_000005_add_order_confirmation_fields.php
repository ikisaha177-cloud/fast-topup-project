<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan jejak verifikasi pesanan ke transaksi top up & pesanan marketplace:
 *
 * - payment_claimed_at : pembeli menandai "sudah bayar" (menunggu verifikasi admin).
 * - confirmed_at       : waktu admin memutuskan (berhasil maupun gagal).
 * - confirmed_by       : admin yang memutuskan (audit trail).
 * - failure_reason     : alasan singkat saat admin menolak/menggagalkan pesanan.
 */
class AddOrderConfirmationFields extends Migration
{
    /** @var array<int, string> */
    private $tables = ['transactions', 'marketplace_orders'];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamp('payment_claimed_at')->nullable()->after('paid_at');
                $blueprint->timestamp('confirmed_at')->nullable()->after('payment_claimed_at');
                $blueprint->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
                $blueprint->string('failure_reason', 255)->nullable()->after('confirmed_by');

                $blueprint->index('payment_claimed_at');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['payment_claimed_at']);
                $blueprint->dropColumn([
                    'payment_claimed_at',
                    'confirmed_at',
                    'confirmed_by',
                    'failure_reason',
                ]);
            });
        }
    }
}
