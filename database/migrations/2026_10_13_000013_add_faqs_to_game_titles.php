<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFaqsToGameTitles extends Migration
{
    /**
     * FAQ per game disimpan sebagai JSON terstruktur (question/answer),
     * bukan teks HTML mentah, supaya aman dirender dan mudah diperbarui.
     */
    public function up()
    {
        Schema::table('game_titles', function (Blueprint $table) {
            $table->json('faqs')->nullable()->after('icon');
        });
    }

    public function down()
    {
        Schema::table('game_titles', function (Blueprint $table) {
            $table->dropColumn('faqs');
        });
    }
}
