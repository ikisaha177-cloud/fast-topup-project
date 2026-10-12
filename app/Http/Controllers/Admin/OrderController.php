<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReopenOrderRequest;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\ActivityLog;
use App\Models\MarketplaceOrder;
use App\Services\MarketplaceOrderService;
use App\Support\ActivityLogger;
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
     * Daftar semua pesanan marketplace (dipantau admin).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $orders = MarketplaceOrder::with(['buyer', 'items.product', 'confirmedBy'])
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
            ->when(in_array($status, OrderStatus::all(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total'     => MarketplaceOrder::count(),
            'pending'   => MarketplaceOrder::where('status', OrderStatus::PENDING)->count(),
            'review'    => MarketplaceOrder::where('status', OrderStatus::PENDING)->whereNotNull('payment_claimed_at')->count(),
            'success'   => MarketplaceOrder::where('status', OrderStatus::SUCCESS)->count(),
            'revenue'   => MarketplaceOrder::where('status', OrderStatus::SUCCESS)->sum('total_amount'),
        ];

        return view('admin.orders.index', compact('orders', 'summary', 'search', 'status'));
    }

    /**
     * Detail satu pesanan marketplace + riwayat aktivitasnya.
     */
    public function show($id)
    {
        $order = MarketplaceOrder::with(['buyer', 'items.product', 'confirmedBy'])->findOrFail($id);

        $this->authorize('view', $order);

        $activity = $this->activityFor($order);

        return view('admin.orders.show', compact('order', 'activity'));
    }

    /**
     * Buka kembali pesanan yang sudah ditolak/dibatalkan (perbaiki salah klik).
     */
    public function reopen(ReopenOrderRequest $request, $id)
    {
        $order = MarketplaceOrder::findOrFail($id);

        $this->authorize('reopen', $order);

        if (! OrderStatus::isReopenable($order->status)) {
            return back()->with('error', 'Hanya pesanan berstatus Gagal atau Dibatalkan yang dapat dibuka kembali.');
        }

        $changed = $this->orders->transitionTo($order, OrderStatus::PENDING, true, [
            'actor_id' => auth()->id(),
        ]);

        if (! $changed) {
            return back()->with('error', 'Pesanan ' . $order->order_code . ' tidak dapat dibuka kembali.');
        }

        ActivityLogger::log(
            'order.reopen',
            'Pesanan ' . $order->order_code . ' dibuka kembali menjadi Menunggu Konfirmasi oleh admin.',
            $order
        );

        return back()->with('success', 'Pesanan ' . $order->order_code . ' dibuka kembali dan menunggu konfirmasi ulang.');
    }

    /**
     * Riwayat aktivitas yang terkait pesanan ini.
     */
    private function activityFor(MarketplaceOrder $order)
    {
        return ActivityLog::where('subject_type', MarketplaceOrder::class)
            ->where('subject_id', $order->id)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();
    }

    /**
     * Ubah status pesanan marketplace (verifikasi manual oleh admin).
     * Pakai service agar stock, jejak verifikasi, & notifikasi ikut konsisten.
     */
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $order = MarketplaceOrder::findOrFail($id);

        $this->authorize('updateStatus', $order);

        $data  = $request->validated();
        $label = OrderStatus::label($data['status']);

        $changed = $this->orders->transitionTo($order, $data['status'], true, [
            'actor_id' => auth()->id(),
            'reason'   => $data['failure_reason'] ?? null,
        ]);

        if (! $changed) {
            $message = OrderStatus::isFinal($order->status)
                ? 'Pesanan ' . $order->order_code . ' sudah final (' . OrderStatus::label($order->status) . ') dan tidak dapat diubah lagi.'
                : 'Status pesanan ' . $order->order_code . ' tidak berubah.';

            return back()->with('success', $message);
        }

        ActivityLogger::log(
            'order.status',
            'Pesanan ' . $order->order_code . ' ditandai "' . $label . '" oleh admin.',
            $order
        );

        return back()->with('success', 'Pesanan ' . $order->order_code . ' berhasil ditandai "' . $label . '".');
    }
}
