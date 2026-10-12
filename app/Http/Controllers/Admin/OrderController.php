<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\MarketplaceOrder;
use App\Services\MarketplaceOrderService;
use App\Support\ActivityLogger;
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
     * Daftar semua pesanan marketplace (dipantau admin).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $orders = MarketplaceOrder::with(['buyer', 'items.product'])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('buyer', function ($buyer) use ($search) {
                            $buyer->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, ['pending', 'success', 'cancelled', 'failed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total'     => MarketplaceOrder::count(),
            'pending'   => MarketplaceOrder::where('status', 'pending')->count(),
            'success'   => MarketplaceOrder::where('status', 'success')->count(),
            'revenue'   => MarketplaceOrder::where('status', 'success')->sum('total_amount'),
        ];

        return view('admin.orders.index', compact('orders', 'summary', 'search', 'status'));
    }

    /**
     * Ubah status pesanan marketplace (verifikasi manual oleh admin).
     * Pakai service agar stock + notifikasi ikut konsisten.
     */
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $order = MarketplaceOrder::findOrFail($id);

        $changed = $this->orders->transitionTo($order, $request->validated()['status']);

        if (! $changed) {
            return back()->with('success', 'Status pesanan ' . $order->order_code . ' tidak berubah.');
        }

        ActivityLogger::log('order.status', 'Mengubah status pesanan ' . $order->order_code . ' menjadi ' . $request->validated()['status'] . '.', $order);

        return back()->with('success', 'Status pesanan ' . $order->order_code . ' diubah menjadi ' . $request->validated()['status'] . '.');
    }
}
