<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Index kolom `status` — dashboard, filter daftar, dan laporan terus
 *    memfilter kolom ini, jadi indexnya wajib ada begitu data bertambah.
 *
 * 2. `confirmation_source` — mencatat SIAPA yang mengonfirmasi pesanan:
 *    'admin' (verifikasi manual) atau 'gateway' (webhook pembayaran otomatis).
 *    Tanpa kolom ini, jejak audit tidak jujur: pesanan yang ditandai selesai
 *    oleh webhook akan tampak seolah diverifikasi admin.
 */
class AddStatusIndexesAndConfirmationSource extends Migration
{
    /** @var array<int, string> */
    private $tables = ['transactions', 'marketplace_orders'];

    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->index('status');
                $blueprint->string('confirmation_source', 16)->nullable()->after('confirmed_by');
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['status']);
                $blueprint->dropColumn('confirmation_source');
            });
        }
    }
}
