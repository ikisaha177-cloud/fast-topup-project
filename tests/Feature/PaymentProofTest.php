<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bukti pembayaran: pembeli boleh mengunggah gambar bukti saat menandai
 * "Saya Sudah Bayar"; admin melihatnya saat memverifikasi.
 *
 * Unggahan bersifat opsional supaya alur lama (tanpa bukti) tetap jalan.
 *
 * Kelas ini menguji alur verifikasi MANUAL (bukti hanya lampiran; admin yang
 * memutuskan), jadi mode verifikasi disematkan ke "manual". Perilaku
 * auto-verifikasi dari bukti diuji terpisah di PaymentVerificationModeTest.
 */
class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['payment.verification' => 'manual']);

        Notification::fake();
        Storage::fake('local');

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('role', 'admin')->first();
    }

    private function buyer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function makeTransaction(User $user, Product $product): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TXN-PROOF-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);
    }

    private function makeOrder(User $buyer, MarketplaceProduct $product): MarketplaceOrder
    {
        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-PROOF-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'pending',
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        return $order;
    }

    public function test_marketplace_payment_proof_is_private_to_owner_and_admin()
    {
        $buyer = $this->buyer();
        $order = $this->makeOrder($buyer, MarketplaceProduct::first());

        $this->actingAs($buyer)->post(route('marketplace.payment.claim', $order->order_code), [
            'payment_proof' => UploadedFile::fake()->image('marketplace-proof.jpg'),
        ]);

        $this->actingAs($this->admin())
            ->get(route('payment-proof.show', ['type' => 'marketplace', 'code' => $order->order_code]))
            ->assertOk();

        $this->actingAs($this->buyer())
            ->get(route('payment-proof.show', ['type' => 'marketplace', 'code' => $order->order_code]))
            ->assertForbidden();
    }

    /** Bukti tersimpan & status tetap menunggu konfirmasi admin. */
    public function test_transaction_claim_with_proof_stores_file()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 640, 480),
            ])
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertNotNull($transaction->payment_claimed_at);
        $this->assertTrue($transaction->hasPaymentProof());
        Storage::disk('local')->assertExists($transaction->payment_proof);
        $this->assertStringStartsWith('payment-proofs/', $transaction->payment_proof);
    }

    public function test_payment_proof_is_private_and_only_visible_to_owner_or_admin()
    {
        $buyer = $this->buyer();
        $otherBuyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)->post(route('topup.payment.claim', $transaction->transaction_code), [
            'payment_proof' => UploadedFile::fake()->image('private-proof.jpg'),
        ]);

        $this->actingAs($otherBuyer)
            ->get(route('payment-proof.show', ['type' => 'topup', 'code' => $transaction->transaction_code]))
            ->assertForbidden();

        $this->actingAs($buyer)
            ->get(route('payment-proof.show', ['type' => 'topup', 'code' => $transaction->transaction_code]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        auth()->logout();
        $this->get(route('payment-proof.show', ['type' => 'topup', 'code' => $transaction->transaction_code]))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('payment-proof.show', ['type' => 'topup', 'code' => $transaction->transaction_code]))
            ->assertOk();
    }

    /** Bukti bisa dilengkapi setelah menandai bayar (selama masih pending). */
    public function test_transaction_can_attach_proof_after_claim()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        // Tandai bayar tanpa bukti.
        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code));
        $this->assertFalse($transaction->fresh()->hasPaymentProof());

        // Susulkan bukti.
        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('susulan.png', 600, 400),
            ]);

        $transaction->refresh();

        $this->assertTrue($transaction->hasPaymentProof());
        Storage::disk('local')->assertExists($transaction->payment_proof);
    }

    /** Tanpa unggah, penandaan tetap berhasil (alur lama tidak rusak). */
    public function test_transaction_claim_without_proof_still_works()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code))
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertNotNull($transaction->payment_claimed_at);
        $this->assertFalse($transaction->hasPaymentProof());
    }

    /** Berkas bukan gambar ditolak validasi. */
    public function test_transaction_claim_rejects_non_image()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->create('virus.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasErrors('payment_proof');

        $this->assertNull($transaction->fresh()->payment_claimed_at);
    }

    /** Admin melihat bukti pada halaman detail transaksi. */
    public function test_admin_sees_transaction_proof()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)->post(route('topup.payment.claim', $transaction->transaction_code), [
            'payment_proof' => UploadedFile::fake()->image('bukti.png', 800, 600),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.transactions.show', $transaction->id))
            ->assertOk()
            ->assertSee('Bukti Pembayaran')
            ->assertSee($transaction->fresh()->paymentProofUrl(), false);
    }

    /** Pesanan marketplace juga menyimpan & menampilkan bukti. */
    public function test_marketplace_order_claim_stores_and_shows_proof()
    {
        $buyer = $this->buyer();
        $order = $this->makeOrder($buyer, MarketplaceProduct::first());

        $this->actingAs($buyer)
            ->post(route('marketplace.payment.claim', $order->order_code), [
                'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 720, 540),
            ])
            ->assertRedirect(route('marketplace.success', $order->order_code));

        $order->refresh();
        $this->assertTrue($order->hasPaymentProof());
        Storage::disk('local')->assertExists($order->payment_proof);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order->id))
            ->assertOk()
            ->assertSee('Bukti Pembayaran')
            ->assertSee($order->paymentProofUrl(), false);
    }

    /** Buka kembali menghapus bukti (jejak verifikasi dibersihkan) + file dibuang. */
    public function test_reopen_clears_and_deletes_proof()
    {
        $admin = $this->admin();
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)->post(route('topup.payment.claim', $transaction->transaction_code), [
            'payment_proof' => UploadedFile::fake()->image('bukti.jpg', 500, 500),
        ]);

        $transaction->refresh();
        $path = $transaction->payment_proof;
        Storage::disk('local')->assertExists($path);

        // Admin menandai gagal, lalu membuka kembali.
        $this->actingAs($admin)
            ->post(route('admin.transactions.status', $transaction->id), ['status' => 'failed']);

        $this->actingAs($admin)
            ->post(route('admin.transactions.reopen', $transaction->id), ['status' => 'pending']);

        $transaction->refresh();

        $this->assertSame('pending', $transaction->status);
        $this->assertNull($transaction->payment_proof);
        $this->assertNull($transaction->payment_claimed_at);
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Berkas unggahan yang ditolak (karena pesanan sudah punya bukti) otomatis
     * dibersihkan dari disk supaya tidak menumpuk sebagai berkas yatim.
     */
    public function test_redundant_proof_upload_is_deleted_when_claim_is_rejected()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        // Klaim pertama dengan bukti — berkas primer tersimpan.
        $this->actingAs($buyer)->post(route('topup.payment.claim', $transaction->transaction_code), [
            'payment_proof' => UploadedFile::fake()->image('bukti1.jpg', 600, 400),
        ]);
        $primaryProof = $transaction->fresh()->payment_proof;
        Storage::disk('local')->assertExists($primaryProof);

        // Klaim kedua dengan bukti baru pada pesanan yang sudah berbukti:
        // klaim ditolak (false), dan berkas kedua yang baru di-upload harus langsung dihapus.
        $this->actingAs($buyer)->post(route('topup.payment.claim', $transaction->transaction_code), [
            'payment_proof' => UploadedFile::fake()->image('bukti2.jpg', 600, 400),
        ]);

        $allFiles = Storage::disk('local')->allFiles('payment-proofs');
        $this->assertCount(1, $allFiles);
        $this->assertSame($primaryProof, $allFiles[0]);
    }
}
