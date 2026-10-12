@extends('layouts.app')

@section('title', 'Riwayat Transaksi - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Riwayat Transaksi</h1>
                <p class="section-subtitle mb-0">Semua transaksi top up milik akunmu.</p>
            </div>
            <a href="{{ route('orders.index') }}" class="btn btn-ghost">Pesanan Saya</a>
        </div>

        @php
            $statusOptions = ['pending' => 'Menunggu Bayar', 'success' => 'Berhasil', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal'];
            $badge = fn($s) => $s === 'success' ? 'badge-success' : ($s === 'pending' ? 'badge-pending' : ($s === 'cancelled' ? 'badge-cancelled' : 'badge-failed'));
            $label = fn($s) => $statusOptions[$s] ?? $s;
        @endphp

        <div class="chips" style="margin-bottom:20px;">
            <a href="{{ route('transactions.index') }}" class="chip {{ $status ? '' : 'active' }}">Semua</a>
            @foreach($statusOptions as $value => $text)
                <a href="{{ route('transactions.index', ['status' => $value]) }}" class="chip {{ $status === $value ? 'active' : '' }}">{{ $text }}</a>
            @endforeach
        </div>

        @if($transactions->isEmpty())
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'receipt', 'size' => 34])</span>
                Belum ada transaksi.
                <div class="mt-16">
                    <a href="{{ route('topup.index') }}" class="btn btn-primary">Mulai Top Up</a>
                </div>
            </div>
        @else
            <div class="card table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Game</th>
                            <th>Produk</th>
                            <th>User ID</th>
                            <th>Metode</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            <tr>
                                <td class="mono">{{ $trx->transaction_code }}</td>
                                <td>{{ $trx->gameTitle->name ?? '-' }}</td>
                                <td>{{ $trx->product->name ?? '-' }}</td>
                                <td>{{ $trx->user_game_id }}</td>
                                <td>{{ $trx->payment_method }}</td>
                                <td>Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $badge($trx->status) }}">{{ $label($trx->status) }}</span></td>
                                <td>{{ $trx->created_at->format('d-m-Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $trx->transaction_code) }}" class="btn btn-outline btn-sm">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center muted">Tidak ada transaksi yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $transactions->links() }}
        @endif
    </div>
</section>
@endsection
