<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Riwayat pesanan marketplace milik pembeli.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $orders = MarketplaceOrder::where('user_id', auth()->id())
            ->withCount('items')
            ->when(in_array($status, ['pending', 'success', 'failed', 'cancelled'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', compact('orders', 'status'));
    }

    /**
     * Detail satu pesanan marketplace milik pembeli.
     */
    public function show($code)
    {
        $order = MarketplaceOrder::where('order_code', $code)
            ->with(['items.product', 'buyer'])
            ->firstOrFail();

        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        return view('orders.show', compact('order'));
    }
}
