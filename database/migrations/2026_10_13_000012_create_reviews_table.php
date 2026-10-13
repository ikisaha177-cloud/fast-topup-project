<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ulasan pembeli untuk produk marketplace.
 *
 * Prinsip kejujuran yang dijaga skema ini:
 *   1. Setiap ulasan WAJIB berasal dari pesanan nyata (`marketplace_order_id`),
 *      jadi tidak ada rating yang bisa muncul tanpa pembelian terverifikasi.
 *   2. Satu pembeli hanya punya SATU ulasan per produk. Kuncinya
 *      (user_id, marketplace_product_id) sekaligus mencegah satu akun
 *      menggandakan rating produk yang sama lewat pembelian berulang —
 *      rata-rata tetap dihitung dari orang yang berbeda, bukan dari
 *      banyaknya pesanan.
 *   3. Produk tanpa ulasan berarti benar-benar tidak ada baris di sini,
 *      sehingga halaman bisa berkata "Belum ada ulasan" dengan jujur
 *      (bukan "0.0" hasil tebakan).
 */
class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_product_id')
                ->constrained('marketplace_products')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Asal ulasan: pesanan yang membuat pembeli berhak mengulas.
            // Nullable + nullOnDelete supaya rating tidak ikut hilang bila
            // baris pesanannya (secara administratif) pernah dibersihkan.
            $table->foreignId('marketplace_order_id')
                ->nullable()
                ->constrained('marketplace_orders')
                ->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'marketplace_product_id'], 'reviews_user_product_unique');
            $table->index(['marketplace_product_id', 'created_at'], 'reviews_product_recent_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
