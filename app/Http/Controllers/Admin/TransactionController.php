<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStatusRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ActivityLogger;
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

        $transactions = Transaction::with(['gameTitle', 'product', 'user'])
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
            ->when(in_array($status, ['pending', 'success', 'cancelled', 'failed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total'     => Transaction::count(),
            'pending'   => Transaction::where('status', 'pending')->count(),
            'success'   => Transaction::where('status', 'success')->count(),
            'cancelled' => Transaction::whereIn('status', ['cancelled', 'failed'])->count(),
            'revenue'   => Transaction::where('status', 'success')->sum('amount'),
        ];

        return view('admin.transactions.index', compact('transactions', 'summary', 'search', 'status'));
    }

    /**
     * Ubah status transaksi (verifikasi manual oleh admin).
     * Pakai service agar stock + notifikasi ikut konsisten.
     */
    public function updateStatus(UpdateStatusRequest $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $this->authorize('updateStatus', $transaction);

        $changed = $this->transactions->transitionTo($transaction, $request->validated()['status']);

        if (! $changed) {
            return back()->with('success', 'Status transaksi ' . $transaction->transaction_code . ' tidak berubah.');
        }

        ActivityLogger::log('transaction.status', 'Mengubah status transaksi ' . $transaction->transaction_code . ' menjadi ' . $request->validated()['status'] . '.', $transaction);

        return back()->with('success', 'Status transaksi ' . $transaction->transaction_code . ' diubah menjadi ' . $request->validated()['status'] . '.');
    }
}
