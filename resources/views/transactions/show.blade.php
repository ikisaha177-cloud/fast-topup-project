@extends('layouts.app')

@php
    $status = $transaction->status;
    $badge = [$transaction->statusBadge(), $transaction->statusLabel()];
@endphp

@section('title', 'Detail Transaksi - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('transactions.index') }}">Riwayat Transaksi</a> / {{ $transaction->transaction_code }}
        </p>

        <div class="steps">
            <div class="step done">Data &amp; Nominal</div>
            <div class="step {{ $status === 'pending' ? 'current' : 'done' }}">Pembayaran</div>
            <div class="step {{ $status === 'success' ? 'done' : '' }}">
                {{ $status === 'cancelled' ? 'Dibatalkan' : 'Selesai' }}
            </div>
        </div>

        <div class="detail-grid">
            <div class="card">
                <div class="flex-between">
                    <h1 style="font-size:22px;" class="mb-0">Detail Transaksi</h1>
                    <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                </div>

                <div class="summary-row"><span>Kode Transaksi</span><span class="mono">{{ $transaction->transaction_code }}</span></div>
                <div class="summary-row"><span>Game</span><span>{{ $transaction->gameTitle->name ?? '-' }}</span></div>
                <div class="summary-row"><span>Produk</span><span>{{ $transaction->product->name ?? '-' }}</span></div>
                <div class="summary-row"><span>User ID</span><span class="mono">{{ $transaction->user_game_id }}</span></div>
                <div class="summary-row"><span>Server ID</span><span>{{ $transaction->server_id ?: '-' }}</span></div>
                <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                <div class="summary-row"><span>Tanggal</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                @if($transaction->confirmed_at)
                    <div class="summary-row"><span>Diverifikasi Admin</span><span>{{ $transaction->confirmed_at->format('d-m-Y H:i') }}</span></div>
                @endif
                @if($transaction->failure_reason)
                    <div class="summary-row"><span>Alasan Gagal</span><span>{{ $transaction->failure_reason }}</span></div>
                @endif

                <div class="summary-total">
                    <span>Total</span>
                    <span class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="card summary-card">
                <h3>Lanjutkan</h3>
                @if($status === 'pending')
                    <p class="muted">
                        @if($transaction->isPaymentClaimed())
                            Pembayaranmu sedang menunggu verifikasi admin.
                        @else
                            Transaksi ini masih menunggu pembayaran.
                        @endif
                    </p>
                    <a href="{{ route('topup.success', $transaction->transaction_code) }}" class="btn btn-success btn-block mt-16">
                        {{ $transaction->isPaymentClaimed() ? 'Lihat Status' : 'Bayar Sekarang' }}
                    </a>
                @else
                    <p class="muted">Transaksi sudah selesai diproses.</p>
                @endif
                <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-block mt-16">Kembali ke Riwayat</a>
                <a href="{{ route('topup.index') }}" class="btn btn-outline btn-block mt-16">Top Up Lagi</a>
            </div>
        </div>
    </div>
</section>
@endsection
