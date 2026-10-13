<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kode promo (voucher) untuk checkout top up & marketplace.
 *
 * - tipe "percent" : potongan persentase dari subtotal, bisa dibatasi max_discount.
 * - tipe "fixed"   : potongan nominal tetap (rupiah).
 * - scope          : "all" berlaku untuk keduanya; selain itu hanya untuk satu layanan.
 * - usage_limit    : null = tanpa batas; kalau diisi, used_count tidak boleh melebihi.
 */
class CreatePromoCodesTable extends Migration
{
    public function up()
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('type')->default('percent');
            $table->unsignedBigInteger('value')->default(0);
            $table->unsignedBigInteger('min_spend')->default(0);
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->string('scope')->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'ends_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_codes');
    }
}
