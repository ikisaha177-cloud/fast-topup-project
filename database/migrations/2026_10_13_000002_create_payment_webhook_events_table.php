<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentWebhookEventsTable extends Migration
{
    /**
     * Menyimpan webhook pembayaran yang sudah diproses supaya pemrosesan
     * ulang (retry dari gateway) bersifat idempotent.
     */
    public function up()
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();      // ID unik dari gateway
            $table->string('provider')->nullable();    // simulation | midtrans
            $table->string('reference')->nullable();   // TXN-... / MKT-...
            $table->string('status')->nullable();      // status internal hasil pemetaan
            $table->json('payload')->nullable();       // payload mentah (audit)
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('reference');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_webhook_events');
    }
}
