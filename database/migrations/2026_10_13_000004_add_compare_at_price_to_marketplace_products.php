<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompareAtPriceToMarketplaceProducts extends Migration
{
    /**
     * Harga coret (harga sebelum diskon). Null = tidak ada diskon.
     */
    public function up()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->unsignedBigInteger('compare_at_price')->nullable()->after('price');
        });
    }

    public function down()
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn('compare_at_price');
        });
    }
}
