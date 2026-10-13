<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Services\MarketplaceOrderService;
use App\Support\OrderStatus;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** @var \App\Services\MarketplaceOrderService */
    protected $orders;

    public function __construct(MarketplaceOrderService $orders)
    {
        $this->orders = $orders;
    }

    /**
     * Riwayat pesanan marketplace milik pembeli.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        // Lazy expiry: riwayat harus jujur, jadi pesanan yang sudah lewat batas
        // waktu dibatalkan sebelum daftarnya dirender.
        $this->orders->expireOverdue(function ($query) {
            $query->where('user_id', auth()->id());
        });

        $orders = MarketplaceOrder::where('user_id', auth()->id())
            ->with(['items.product'])
            ->withCount('items')
            ->when(in_array($status, OrderStatus::all(), true), function ($query) use ($status) {
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
            ->with(['items.product', 'buyer', 'confirmedBy', 'revokedBy'])
            ->firstOrFail();

        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $this->orders->expireIfDue($order);

        return view('orders.show', compact('order'));
    }
}
