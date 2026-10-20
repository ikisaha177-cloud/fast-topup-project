<?php

namespace Tests\Feature;

use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Support\PaymentChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Portal pembayaran — halaman TERPISAH ("web lain") tempat pembeli menjalankan
 * pembayaran QRIS / e-wallet / transfer bank, lalu menerima STRUK yang bisa
 * di-screenshot dan diunggah sebagai bukti.
 *
 * Yang diuji:
 *  - instruksi bayar per metode (QR, Virtual Account, nomor e-wallet)
 *  - konfirmasi pembayaran hanya menandai "sudah bayar", tidak mengambil
 *    alih keputusan verifikasi
 *  - struk muncul setelah konfirmasi dan tetap jujur soal status
 *  - otorisasi: pemilik, guest (guest checkout top up), admin diblokir
 */
class PaymentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mode bawaan (hybrid): tanpa bukti tetap menunggu admin, dengan bukti
        // otomatis Selesai — sama persis dengan alur tombol di toko.
        config(['payment.verification' => 'hybrid']);

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

    private function makeTransaction(User $user, Product $product, string $method = 'QRIS'): Transaction
    {
        return Transaction::create([
            'transaction_code' => 'TXN-PORTAL-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => $method,
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);
    }

    private function makeOrder(User $buyer, MarketplaceProduct $product, string $method = 'QRIS', int $quantity = 1): MarketplaceOrder
    {
        $order = MarketplaceOrder::create([
            'order_code'     => 'MKT-PORTAL-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price * $quantity,
            'payment_method' => $method,
            'status'         => 'pending',
        ]);

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => $quantity,
            'price'                  => $product->price,
            'subtotal'               => $product->price * $quantity,
        ]);

        return $order;
    }

    /* ------------------------------------------------------------------
     | Instruksi pembayaran per metode
     * --------------------------------------------------------------- */

    /** QRIS menampilkan kode QR dan nominal yang benar. */
    public function test_portal_shows_qr_instructions_for_qris()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Portal Pembayaran')
            ->assertSee($transaction->transaction_code)
            ->assertSee('Pindai kode QR')
            ->assertSee('Rp ' . number_format($transaction->amount, 0, ',', '.'))
            ->assertSee('Saya Sudah Bayar')
            ->assertSee('aria-label="QR pembayaran ' . $transaction->transaction_code . '"', false);
    }

    /** Transfer bank menampilkan nomor Virtual Account yang deterministik. */
    public function test_portal_shows_virtual_account_for_bank_transfer()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), 'Bank Transfer');

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Transfer ke Virtual Account')
            ->assertSee('Nomor Virtual Account')
            ->assertSee(PaymentChannel::virtualAccount($transaction->transaction_code), false);
    }

    /** E-wallet menampilkan nomor tujuan. */
    public function test_portal_shows_destination_number_for_ewallet()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first(), 'DANA');

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Bayar lewat DANA')
            ->assertSee('Nomor Tujuan DANA')
            ->assertSee(PaymentChannel::ewalletNumber($transaction->transaction_code), false);
    }

    /**
     * Nomor akun diturunkan dari kode transaksi: selalu sama untuk pesanan
     * yang sama, dan berformat sah.
     */
    public function test_payment_channel_account_numbers_are_deterministic_and_well_formed()
    {
        $first = PaymentChannel::forMethod('Bank Transfer', 'TXN-DETERMINISTIC', 100000);
        $again = PaymentChannel::forMethod('Bank Transfer', 'TXN-DETERMINISTIC', 100000);

        $this->assertSame($first['account'], $again['account']);
        $this->assertMatchesRegularExpression('/^8808 \d{4} \d{4}$/', $first['account']);

        $this->assertMatchesRegularExpression(
            '/^08\d{2}-\d{4}-\d{4}$/',
            PaymentChannel::ewalletNumber('TXN-DETERMINISTIC')
        );

        $qr = PaymentChannel::forMethod('QRIS', 'TXN-DETERMINISTIC', 100000);
        $this->assertNull($qr['account']);
        $this->assertSame('qr', $qr['type']);
    }

    /* ------------------------------------------------------------------
     | Konfirmasi pembayaran -> struk
     * --------------------------------------------------------------- */

    /** Menekan "Saya Sudah Bayar" hanya menandai sudah bayar, lalu struk muncul. */
    public function test_confirm_marks_payment_claimed_and_shows_receipt()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->post(route('payment.confirm', $transaction->transaction_code))
            ->assertRedirect(route('payment.show', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertNotNull($transaction->payment_claimed_at);
        // hybrid tanpa bukti: portal tidak mengambil alih keputusan verifikasi.
        $this->assertSame('pending', $transaction->status);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Struk Pembayaran')
            ->assertSee('Pembayaran Diterima')
            ->assertSee('Menunggu verifikasi admin')
            ->assertSee('Unggah Struk sebagai Bukti')
            ->assertDontSee('Saya Sudah Bayar');
    }

    /** Konfirmasi ganda tidak merusak keadaan (idempoten). */
    public function test_confirm_is_idempotent()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)->post(route('payment.confirm', $transaction->transaction_code));
        $this->actingAs($buyer)
            ->post(route('payment.confirm', $transaction->transaction_code))
            ->assertRedirect(route('payment.show', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertNotNull($transaction->payment_claimed_at);
        $this->assertSame('pending', $transaction->status);
        $this->assertFalse($transaction->hasPaymentProof());
    }

    /** Struk untuk pesanan yang sudah Selesai menyatakan "Pembayaran Berhasil". */
    public function test_completed_transaction_shows_paid_receipt()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $transaction->update([
            'status'              => 'success',
            'payment_claimed_at'  => now(),
            'confirmed_at'        => now(),
            'confirmation_source' => 'admin',
        ]);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Struk Pembayaran')
            ->assertSee('Pembayaran Berhasil')
            ->assertSee('Pesanan selesai diproses')
            ->assertSee('Rp ' . number_format($transaction->amount, 0, ',', '.'));
    }

    /** Pesanan batal/gagal tidak lagi menampilkan tombol bayar. */
    public function test_cancelled_transaction_shows_closed_state()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $transaction->update(['status' => 'cancelled']);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Pesanan Tidak Dapat Dibayar')
            ->assertDontSee('Saya Sudah Bayar')
            ->assertDontSee('Struk Pembayaran');
    }

    /**
     * Pesanan yang pernah ditandai sudah bayar lalu DITOLAK admin tidak boleh
     * tetap menampilkan struk "Pembayaran Diterima" — itu menyesatkan pembeli.
     */
    public function test_rejected_transaction_shows_closed_state_not_receipt()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $transaction->update([
            'status'             => 'failed',
            'payment_claimed_at' => now(),
            'failure_reason'     => 'Bukti pembayaran tidak valid',
        ]);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Pesanan Tidak Dapat Dibayar')
            ->assertSee('Gagal')
            ->assertDontSee('Pembayaran Diterima')
            ->assertDontSee('Struk Pembayaran');
    }

    /* ------------------------------------------------------------------
     | Alur lengkap: portal -> konfirmasi -> unggah struk
     * --------------------------------------------------------------- */
    /**
     * Konfirmasi lalu unggah struk di halaman pesanan: bukti yang melengkapi
     * klaim membuat pesanan Selesai otomatis pada mode hybrid.
     */
    public function test_portal_confirm_then_proof_upload_completes_order()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)->post(route('payment.confirm', $transaction->transaction_code));

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertTrue($transaction->fresh()->isPaymentClaimed());

        $this->actingAs($buyer)
            ->post(route('topup.payment.claim', $transaction->transaction_code), [
                'payment_proof' => UploadedFile::fake()->image('struk.jpg', 640, 480),
            ])
            ->assertRedirect(route('topup.success', $transaction->transaction_code));

        $transaction->refresh();

        $this->assertSame('success', $transaction->status);
        $this->assertSame('auto', $transaction->confirmation_source);
        Storage::disk('local')->assertExists($transaction->payment_proof);

        $this->actingAs($buyer)
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Pembayaran Berhasil');
    }

    /** Halaman sukses di toko menautkan pembeli ke portal pembayaran. */
    public function test_success_page_links_to_payment_portal()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($buyer)
            ->get(route('topup.success', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Bayar Sekarang di Portal Pembayaran')
            ->assertSee(route('payment.show', $transaction->transaction_code), false);
    }

    /* ------------------------------------------------------------------
     | Marketplace
     * --------------------------------------------------------------- */

    /** Portal untuk pesanan marketplace: instruksi, konfirmasi, lalu struk. */
    public function test_marketplace_order_uses_portal_and_receipt()
    {
        $buyer = $this->buyer();
        $order = $this->makeOrder($buyer, MarketplaceProduct::first());

        $this->actingAs($buyer)
            ->get(route('payment.show', $order->order_code))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Saya Sudah Bayar');

        $this->actingAs($buyer)
            ->post(route('payment.confirm', $order->order_code))
            ->assertRedirect(route('payment.show', $order->order_code));

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->payment_claimed_at);

        $this->actingAs($buyer)
            ->get(route('payment.show', $order->order_code))
            ->assertOk()
            ->assertSee('Pembayaran Diterima');
    }

    /** Struk marketplace menampilkan jumlah item dan total pesanan. */
    public function test_marketplace_receipt_lists_quantity_and_total()
    {
        $buyer = $this->buyer();
        $product = MarketplaceProduct::first();
        $order = $this->makeOrder($buyer, $product, 'QRIS', 3);

        $order->update(['payment_claimed_at' => now()]);

        $this->actingAs($buyer)
            ->get(route('payment.show', $order->order_code))
            ->assertOk()
            ->assertSee('Jumlah: 3')
            ->assertSee($product->name)
            ->assertSee('Rp ' . number_format($product->price * 3, 0, ',', '.'));
    }

    /* ------------------------------------------------------------------
     | Otorisasi
     * --------------------------------------------------------------- */

    /** Guest checkout top up (tanpa akun) tetap bisa membuka portal. */
    public function test_guest_can_open_guest_checkout_portal()
    {
        $product = Product::first();

        $transaction = Transaction::create([
            'transaction_code' => 'TXN-GUEST-' . uniqid(),
            'user_id'          => null,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);

        $this->get(route('payment.show', $transaction->transaction_code))
            ->assertOk()
            ->assertSee('Saya Sudah Bayar');
    }

    /** Pembeli lain tidak boleh membuka portal milik pembeli lain. */
    public function test_other_buyer_cannot_open_portal()
    {
        $owner = $this->buyer();
        $transaction = $this->makeTransaction($owner, Product::first());

        $this->actingAs($this->buyer())
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertForbidden();
    }

    /** Portal selalu butuh akun pemilik untuk pesanan marketplace. */
    public function test_marketplace_portal_requires_authenticated_owner()
    {
        $owner = $this->buyer();
        $order = $this->makeOrder($owner, MarketplaceProduct::first());

        $this->get(route('payment.show', $order->order_code))->assertForbidden();

        $this->actingAs($this->buyer())
            ->get(route('payment.show', $order->order_code))
            ->assertForbidden();
    }

    /** Akun admin diarahkan ke dashboard — portal khusus sisi pembeli. */
    public function test_admin_is_redirected_away_from_portal()
    {
        $buyer = $this->buyer();
        $transaction = $this->makeTransaction($buyer, Product::first());

        $this->actingAs($this->admin())
            ->get(route('payment.show', $transaction->transaction_code))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->admin())
            ->post(route('payment.confirm', $transaction->transaction_code))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNull($transaction->fresh()->payment_claimed_at);
    }
}
