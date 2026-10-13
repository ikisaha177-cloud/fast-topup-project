<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
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
     * Riwayat transaksi milik user yang login.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        // Lazy expiry: riwayat harus jujur, jadi transaksi yang sudah lewat
        // batas waktu dibatalkan sebelum daftarnya dirender.
        $this->transactions->expireOverdue(function ($query) {
            $query->where('user_id', auth()->id());
        });

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
            ->with(['gameTitle', 'product', 'user', 'confirmedBy', 'revokedBy'])
            ->firstOrFail();

        if (! auth()->check() || $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        $this->transactions->expireIfDue($transaction);

        return view('transactions.show', compact('transaction'));
    }
}
