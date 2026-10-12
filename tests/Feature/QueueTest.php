<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Notifications\TransactionStatusChanged;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Antrean (queue) untuk notifikasi email.
 *
 * Notifikasi harus bisa dilempar ke antrean supaya request user tidak
 * menunggu proses kirim email, dan tabel antrean harus tersedia.
 */
class QueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_queue_tables_are_migrated()
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasTable('job_batches'));
    }

    public function test_notifications_are_queueable()
    {
        $this->assertInstanceOf(ShouldQueue::class, new WelcomeNotification());
    }

    public function test_status_notifications_are_queueable()
    {
        $transaction = new \App\Models\Transaction();
        $order       = new \App\Models\MarketplaceOrder();

        $this->assertInstanceOf(ShouldQueue::class, new TransactionStatusChanged($transaction, 'Pembayaran diterima'));
        $this->assertInstanceOf(ShouldQueue::class, new OrderStatusChanged($order, 'Pembayaran diterima'));
    }

    public function test_register_queues_welcome_email_instead_of_sending_inline()
    {
        config(['queue.default' => 'database']);

        $this->assertSame(0, DB::table('jobs')->count());

        $this->post(route('register'), [
            'name'                  => 'Pembeli Antrean',
            'email'                 => 'antrean@example.test',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('home'));

        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_queued_welcome_email_is_processed_by_worker()
    {
        config(['queue.default' => 'database']);

        $this->post(route('register'), [
            'name'                  => 'Pembeli Worker',
            'email'                 => 'worker@example.test',
            'password'              => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $this->assertSame(1, DB::table('jobs')->count());

        Artisan::call('queue:work', ['--once' => true]);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertNotNull(User::where('email', 'worker@example.test')->first());
    }
}
