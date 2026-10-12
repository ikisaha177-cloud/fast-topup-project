<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Support\OrderStatus;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Riwayat transaksi milik user yang login.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $transactions = Transaction::where('user_id', auth()->id())
            ->with(['gameTitle', 'product'])
            ->when(in_array($status, OrderStatus::all(), true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('transactions.index', compact('transactions', 'status'));
    }

    /**
     * Detail satu transaksi.
     */
    public function show($code)
    {
        $transaction = Transaction::where('transaction_code', $code)
            ->with(['gameTitle', 'product', 'user'])
            ->firstOrFail();

        if (! auth()->check() || $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        return view('transactions.show', compact('transaction'));
    }
}
