<?php

namespace Tests\Feature;

use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laporan penjualan admin: harian, bulanan, tahunan, filter status, dan ekspor CSV.
 */
class AdminReportTest extends TestCase
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

    private function makeTransaction(User $user, Product $product, array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'transaction_code' => 'TXN-RPT-' . uniqid(),
            'user_id'          => $user->id,
            'game_title_id'    => $product->game_title_id,
            'product_id'       => $product->id,
            'user_game_id'     => '12345678',
            'payment_method'   => 'QRIS',
            'amount'           => $product->price,
            'status'           => 'success',
        ], $attributes));
    }

    private function makeOrder(User $buyer, MarketplaceProduct $product, array $attributes = []): MarketplaceOrder
    {
        $order = MarketplaceOrder::create(array_merge([
            'order_code'     => 'MKT-RPT-' . uniqid(),
            'user_id'        => $buyer->id,
            'total_amount'   => $product->price,
            'payment_method' => 'QRIS',
            'status'         => 'success',
        ], $attributes));

        $order->items()->create([
            'marketplace_product_id' => $product->id,
            'quantity'               => 1,
            'price'                  => $product->price,
            'subtotal'               => $product->price,
        ]);

        return $order;
    }

    public function test_reports_page_loads_for_admin()
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Laporan Penjualan');
    }

    public function test_reports_page_is_forbidden_for_buyer()
    {
        $this->actingAs($this->buyer())
            ->get(route('admin.reports.index'))
            ->assertStatus(403);
    }

    public function test_reports_page_is_redirected_for_guest()
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
    }

    public function test_daily_report_totals_revenue_from_successful_orders_only()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $marketProduct = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->makeTransaction($buyer, $product, ['amount' => 50000, 'status' => 'success']);
        $this->makeTransaction($buyer, $product, ['amount' => 90000, 'status' => 'pending']);
        $this->makeOrder($buyer, $marketProduct, ['total_amount' => 25000, 'status' => 'success']);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.index', [
            'periode' => 'harian',
            'tahun'   => now()->year,
            'bulan'   => now()->month,
        ]));

        $response->assertOk();

        $summary = $response->viewData('report')['summary'];

        $this->assertSame(50000, $summary['topup_revenue']);
        $this->assertSame(25000, $summary['market_revenue']);
        $this->assertSame(75000, $summary['revenue']);
        $this->assertSame(2, $summary['topup_count']);
        $this->assertSame(1, $summary['order_count']);
        $this->assertSame(2, $summary['success_count']);
    }

    public function test_status_filter_only_counts_matching_records()
    {
        $buyer = $this->buyer();
        $product = Product::first();

        $this->makeTransaction($buyer, $product, ['amount' => 10000, 'status' => 'success']);
        $this->makeTransaction($buyer, $product, ['amount' => 20000, 'status' => 'failed']);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.index', [
            'periode' => 'harian',
            'tahun'   => now()->year,
            'bulan'   => now()->month,
            'status'  => 'failed',
        ]));

        $response->assertOk();

        $summary = $response->viewData('report')['summary'];

        $this->assertSame(1, $summary['topup_count']);
        $this->assertSame(0, $summary['topup_revenue']);
    }

    public function test_monthly_and_yearly_reports_return_correct_bucket_counts()
    {
        $admin = $this->admin();

        $monthly = $this->actingAs($admin)->get(route('admin.reports.index', [
            'periode' => 'bulanan',
            'tahun'   => now()->year,
        ]));

        $monthly->assertOk();
        $this->assertCount(12, $monthly->viewData('report')['rows']);

        $yearly = $this->actingAs($admin)->get(route('admin.reports.index', [
            'periode' => 'tahunan',
        ]));

        $yearly->assertOk();
        $this->assertNotEmpty($yearly->viewData('report')['rows']);
    }

    public function test_report_lists_top_games_products_and_payment_methods()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $marketProduct = MarketplaceProduct::where('stock', '>', 0)->first();

        $this->makeTransaction($buyer, $product, ['payment_method' => 'DANA']);
        $this->makeOrder($buyer, $marketProduct, ['payment_method' => 'OVO']);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.index', [
            'periode' => 'harian',
            'tahun'   => now()->year,
            'bulan'   => now()->month,
        ]));

        $response->assertOk();

        $report = $response->viewData('report');

        $this->assertNotEmpty($report['topGames']);
        $this->assertNotEmpty($report['topProducts']);

        $methods = collect($report['paymentMethods'])->pluck('method')->all();
        $this->assertContains('DANA', $methods);
        $this->assertContains('OVO', $methods);

        // Konten benar-benar ter-render di halaman.
        $response->assertSee($product->gameTitle->name)
            ->assertSee('DANA')
            ->assertSee('OVO')
            ->assertSee('Metode Pembayaran');
    }

    public function test_top_lists_ignore_unsuccessful_orders()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $marketProduct = MarketplaceProduct::where('stock', '>', 0)->first();

        // Satu pesanan selesai, satu pesanan gagal untuk game & produk yang sama.
        $this->makeTransaction($buyer, $product, ['amount' => 40000, 'status' => 'success']);
        $this->makeTransaction($buyer, $product, ['amount' => 40000, 'status' => 'failed']);
        $this->makeOrder($buyer, $marketProduct, ['total_amount' => 20000, 'status' => 'success']);
        $this->makeOrder($buyer, $marketProduct, ['total_amount' => 20000, 'status' => 'cancelled']);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.index', [
            'periode' => 'harian',
            'tahun'   => now()->year,
            'bulan'   => now()->month,
        ]));

        $response->assertOk();

        $report = $response->viewData('report');

        // Game terlaris: hanya pesanan selesai yang dihitung.
        $this->assertSame(1, $report['topGames'][0]['count']);
        $this->assertSame(40000, $report['topGames'][0]['revenue']);

        // Produk terlaris: hanya pesanan selesai yang dihitung.
        $this->assertSame(1, $report['topProducts'][0]['quantity']);
        $this->assertSame((int) $marketProduct->price, $report['topProducts'][0]['revenue']);
    }

    public function test_report_csv_export_streams_file()
    {
        $buyer = $this->buyer();
        $product = Product::first();
        $this->makeTransaction($buyer, $product, ['amount' => 30000]);

        $response = $this->actingAs($this->admin())->get(route('admin.reports.export', [
            'periode' => 'harian',
            'tahun'   => now()->year,
            'bulan'   => now()->month,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('laporan-harian', $response->headers->get('content-disposition'));
    }
}
