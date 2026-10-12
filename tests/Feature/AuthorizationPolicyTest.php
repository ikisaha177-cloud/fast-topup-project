<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Otorisasi berbasis Policy (TransactionPolicy / MarketplaceOrderPolicy)
 * + Gate khusus admin.
 */
class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    private function makeTransaction(User $owner = null): Transaction
    {
        $game    = GameTitle::first();
        $product = Product::where('game_title_id', $game->id)->first();

        return Transaction::create([
            'transaction_code' => 'TXN-TEST-' . uniqid(),
            'user_id'          => $owner ? $owner->id : null,
            'game_title_id'    => $game->id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'pending',
        ]);
    }

    public function test_gate_manage_catalog_allows_admin_only()
    {
        $this->assertTrue(Gate::forUser($this->admin())->allows('manage-catalog'));
        $this->assertFalse(Gate::forUser($this->buyer())->allows('manage-catalog'));
    }

    public function test_owner_can_view_own_transaction()
    {
        $buyer = $this->buyer();
        $txn   = $this->makeTransaction($buyer);

        $this->actingAs($buyer)
            ->get(route('topup.success', $txn->transaction_code))
            ->assertOk();
    }

    public function test_other_buyer_cannot_view_someone_elses_transaction()
    {
        $owner = $this->buyer();
        $other = $this->buyer();
        $txn   = $this->makeTransaction($owner);

        $this->actingAs($other)
            ->get(route('topup.success', $txn->transaction_code))
            ->assertStatus(403);
    }

    public function test_admin_policy_allows_viewing_any_transaction()
    {
        $txn = $this->makeTransaction($this->buyer());

        // Policy mengizinkan admin...
        $this->assertTrue(
            Gate::forUser($this->admin())->allows('view', $txn)
        );

        // ...tetapi middleware not-admin tetap mengembalikan admin ke dashboard
        // admin (pemisahan area pembeli vs admin memang disengaja).
        $this->actingAs($this->admin())
            ->get(route('topup.success', $txn->transaction_code))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_transaction_is_viewable_by_code()
    {
        // Top up bisa dilakukan tanpa login; transaksi tanpa pemilik
        // tetap bisa dibuka oleh siapa pun yang tahu kodenya.
        $txn = $this->makeTransaction(null);

        $this->get(route('topup.success', $txn->transaction_code))
            ->assertOk();
    }

    public function test_policy_denies_status_update_for_non_admin()
    {
        $txn = $this->makeTransaction($this->buyer());

        $this->assertFalse(
            Gate::forUser($this->buyer())->allows('updateStatus', $txn)
        );

        $this->assertTrue(
            Gate::forUser($this->admin())->allows('updateStatus', $txn)
        );
    }

    public function test_buyer_cannot_post_admin_status_update()
    {
        $buyer = $this->buyer();
        $txn   = $this->makeTransaction($buyer);

        $this->actingAs($buyer)
            ->post(route('admin.transactions.status', $txn->id), ['status' => 'success'])
            ->assertStatus(403);

        $this->assertSame('pending', $txn->fresh()->status);
    }
}
