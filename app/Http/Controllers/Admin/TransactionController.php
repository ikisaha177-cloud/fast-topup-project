<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReopenOrderRequest;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\ActivityLog;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ActivityLogger;
use App\Support\OrderStatus;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /** @var \App\Services\TransactionService */
    protected $transactions;

    public function __construct(TransactionService $transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * Daftar semua transaksi top up (dipantau admin).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        // Lazy expiry: bersihkan antrean dari pesanan yang sudah lewat batas
        // waktu sebelum admin melihatnya (tanpa cron).
        $this->transactions->expireOverdue();

        $transactions = Transaction::with(['gameTitle', 'product', 'user', 'confirmedBy'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('user_game_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($user) use ($search) {
                            $user->where('name', 'like', "%{$search}%")
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
            'total'     => Transaction::count(),
            'pending'   => Transaction::where('status', OrderStatus::PENDING)->count(),
            'review'    => Transaction::where('status', OrderStatus::PENDING)->whereNotNull('payment_claimed_at')->count(),
            'success'   => Transaction::where('status', OrderStatus::SUCCESS)->count(),
            'cancelled' => Transaction::whereIn('status', [OrderStatus::CANCELLED, OrderStatus::FAILED])->count(),
            'revenue'   => Transaction::where('status', OrderStatus::SUCCESS)->sum('amount'),
        ];

        return view('admin.transactions.index', compact('transactions', 'summary', 'search', 'status'));
    }

    /**
     * Detail satu transaksi + riwayat aktivitasnya (untuk verifikasi admin).
     */
    public function show($id)
    {
        $transaction = Transaction::with(['gameTitle', 'product', 'user', 'confirmedBy', 'revokedBy'])->findOrFail($id);

        $this->authorize('view', $transaction);

        // Lazy expiry: detail harus menampilkan status sebenarnya, bukan
        // "menunggu pembayaran" untuk pesanan yang batas waktunya sudah habis.
        $this->transactions->expireIfDue($transaction);

        $activity = $this->activityFor($transaction);

        return view('admin.transactions.show', compact('transaction', 'activity'));
    }

    /**
     * Buka kembali transaksi yang sudah ditolak/dibatalkan (perbaiki salah klik).
     */
    public function reopen(ReopenOrderRequest $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $this->authorize('reopen', $transaction);

        if (! OrderStatus::isReopenable($transaction->status)) {
            return back()->with('error', 'Hanya transaksi berstatus Gagal atau Dibatalkan yang dapat dibuka kembali.');
        }

        $changed = $this->transactions->transitionTo($transaction, OrderStatus::PENDING, true, [
            'actor_id' => auth()->id(),
        ]);

        if (! $changed) {
            return back()->with('error', 'Transaksi ' . $transaction->transaction_code . ' tidak dapat dibuka kembali.');
        }

        ActivityLogger::log(
            'transaction.reopen',
            'Transaksi ' . $transaction->transaction_code . ' dibuka kembali menjadi Menunggu Konfirmasi oleh admin.',
            $transaction
        );

        return back()->with('success', 'Transaksi ' . $transaction->transaction_code . ' dibuka kembali dan menunggu konfirmasi ulang.');
    }

    /**
     * Riwayat aktivitas yang terkait transaksi ini.
     */
    private function activityFor(Transaction $transaction)
    {
        return ActivityLog::where('subject_type', Transaction::class)
            ->where('subject_id', $transaction->id)
            ->with('user')
            ->latest()
            ->take(20)
            ->get();
    }

    /**
     * Ubah status transaksi (verifikasi manual oleh admin).
     * Pakai service agar stock, jejak verifikasi, & notifikasi ikut konsisten.
     */
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $this->authorize('updateStatus', $transaction);

        $data  = $request->validated();
        $label = OrderStatus::label($data['status']);

        // Pembatalan konfirmasi yang salah (success -> failed) dicatat khusus.
        $revoking = $transaction->status === OrderStatus::SUCCESS
            && $data['status'] === OrderStatus::FAILED;

        $changed = $this->transactions->transitionTo($transaction, $data['status'], true, [
            'actor_id' => auth()->id(),
            'reason'   => $data['failure_reason'] ?? null,
        ]);

        if (! $changed) {
            if ($transaction->status === OrderStatus::SUCCESS) {
                $message = 'Transaksi ' . $transaction->transaction_code . ' sudah berstatus Selesai. Untuk membatalkannya, gunakan aksi "Batalkan Konfirmasi".';
            } elseif (OrderStatus::isFinal($transaction->status)) {
                $message = 'Transaksi ' . $transaction->transaction_code . ' sudah final (' . OrderStatus::label($transaction->status) . ') dan tidak dapat diubah lagi.';
            } else {
                $message = 'Status transaksi ' . $transaction->transaction_code . ' tidak berubah.';
            }

            return back()->with('success', $message);
        }

        if ($revoking) {
            ActivityLogger::log(
                'transaction.revoke',
                'Konfirmasi transaksi ' . $transaction->transaction_code . ' dibatalkan oleh admin (ditandai Gagal); stok dikembalikan.',
                $transaction
            );

            return back()->with('success', 'Konfirmasi transaksi ' . $transaction->transaction_code . ' dibatalkan; status menjadi Gagal dan stok dikembalikan.');
        }

        ActivityLogger::log(
            'transaction.status',
            'Transaksi ' . $transaction->transaction_code . ' ditandai "' . $label . '" oleh admin.',
            $transaction
        );

        return back()->with('success', 'Transaksi ' . $transaction->transaction_code . ' berhasil ditandai "' . $label . '".');
    }
}
