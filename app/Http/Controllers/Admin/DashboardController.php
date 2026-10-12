<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameTitle;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Dashboard admin.
     */
    public function index()
    {
        $stats = [
            'users'                => User::count(),
            'games'                => GameTitle::count(),
            'products'             => Product::count(),
            'transactions'         => Transaction::count(),
            'marketplace_products' => MarketplaceProduct::count(),
            'marketplace_orders'   => MarketplaceOrder::count(),
            'pending'              => Transaction::where('status', 'pending')->count()
                + MarketplaceOrder::where('status', 'pending')->count(),
            'review'               => Transaction::where('status', 'pending')->whereNotNull('payment_claimed_at')->count()
                + MarketplaceOrder::where('status', 'pending')->whereNotNull('payment_claimed_at')->count(),
            'revenue'              => (int) Transaction::where('status', 'success')->sum('amount')
                + (int) MarketplaceOrder::where('status', 'success')->sum('total_amount'),
        ];

        $latestTransactions = Transaction::with(['gameTitle', 'product', 'user'])
            ->latest()
            ->take(10)
            ->get();

        $latestOrders = MarketplaceOrder::with('buyer')
            ->latest()
            ->take(5)
            ->get();

        // Pendapatan 7 hari terakhir (untuk grafik mini di dashboard).
        $revenueSeries = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo)->toDateString();

            $topup = (int) Transaction::where('status', 'success')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $market = (int) MarketplaceOrder::where('status', 'success')
                ->whereDate('created_at', $date)
                ->sum('total_amount');

            return [
                'label'   => now()->subDays($daysAgo)->format('d/m'),
                'revenue' => $topup + $market,
            ];
        });

        $revenuePeak = max(1, $revenueSeries->max('revenue'));

        return view('admin.dashboard', compact('stats', 'latestTransactions', 'latestOrders', 'revenueSeries', 'revenuePeak'));
    }
}
