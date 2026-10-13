@extends('layouts.app')

@section('title', 'Data Transaksi - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Data Transaksi</h1>
                <p class="section-subtitle mb-0">Konfirmasi pembayaran top up. Pesanan berstatus <strong>Selesai</strong> setelah kamu setujui.</p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline">Lihat Laporan</a>
        </div>

        @include('partials.admin-nav')

@php
    $statusOptions = \App\Support\OrderStatus::labels();
    $badge = fn($s) => \App\Support\OrderStatus::badge($s);
    $label = fn($s) => \App\Support\OrderStatus::label($s);
@endphp

@if($summary)
    <div class="kpi-row">
        <div class="card stat-card">
            <span class="stat-number">{{ $summary['total'] }}</span>
            <span class="stat-label">Total Transaksi</span>
        </div>
        <div class="card stat-card">
            <span class="stat-number" style="color:var(--yellow);">{{ $summary['pending'] }}</span>
            <span class="stat-label">Menunggu Konfirmasi</span>
        </div>
        <div class="card stat-card">
            <span class="stat-number" style="color:var(--brand);">{{ $summary['review'] }}</span>
            <span class="stat-label">Perlu Diverifikasi</span>
        </div>
        <div class="card stat-card">
            <span class="stat-number" style="color:var(--green);">{{ $summary['success'] }}</span>
            <span class="stat-label">Selesai</span>
        </div>
        <div class="card stat-card is-accent">
            <span class="stat-number">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</span>
            <span class="stat-label">Pendapatan Top Up</span>
        </div>
    </div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="searchbar">
        <input type="text" name="q" aria-label="Cari transaksi" value="{{ $search }}" placeholder="Cari kode transaksi, User ID, atau nama/email pembeli…">
    </form>
    <div class="chips">
        <a href="{{ route('admin.transactions.index') }}" class="chip {{ $status ? '' : 'active' }}">Semua</a>
        @foreach($statusOptions as $value => $text)
            <a href="{{ route('admin.transactions.index', ['status' => $value]) }}" class="chip {{ $status === $value ? 'active' : '' }}">{{ $text }}</a>
        @endforeach
    </div>
</div>

<div class="card table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Kode</th>
                <th scope="col">User</th>
                <th scope="col">Game / Produk</th>
                <th scope="col">User ID Game</th>
                <th scope="col">Metode</th>
                <th scope="col">Total</th>
                <th scope="col">Status</th>
                <th scope="col">Pembayaran</th>
                <th scope="col">Tanggal</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
                <tr class="{{ $trx->needsReview() ? 'row-highlight' : '' }}">
                    <td>
                        <a class="cell-link mono" href="{{ route('admin.transactions.show', $trx->id) }}"
                           title="Buka detail transaksi">{{ $trx->transaction_code }}</a>
                    </td>
                    <td>{{ $trx->user->name ?? 'Guest' }}</td>
                    <td>
                        <div class="item-cell">
                            @include('partials.game-icon', ['game' => $trx->gameTitle ?? (object) [], 'size' => 'xs'])
                            <span>
                                <span class="item-name">{{ $trx->gameTitle->name ?? '-' }}</span>
                                <span class="item-sub">{{ $trx->product->name ?? '-' }}</span>
                            </span>
                        </div>
                    </td>
                    <td>{{ $trx->user_game_id }}{{ $trx->server_id ? ' (' . $trx->server_id . ')' : '' }}</td>
                    <td>{{ $trx->payment_method }}</td>
                    <td>Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                    <td>
                        <span class="badge {{ $badge($trx->status) }}">{{ $label($trx->status) }}</span>
                        @if($trx->status === 'failed' && $trx->failure_reason)
                            <div class="muted" style="font-size:12px;">{{ $trx->failure_reason }}</div>
                        @endif
                        @if($trx->confirmed_at)
                            <div class="muted" style="font-size:12px;">{{ $trx->confirmationLabel() }}</div>
                        @endif
                    </td>
                    <td>
                        @if($trx->isPaymentClaimed())
                            <span class="badge badge-info">Sudah Bayar</span>
                        @else
                            <span class="muted">Belum</span>
                        @endif
                    </td>
                    <td>{{ $trx->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        @if($trx->status === 'pending')
                            <div class="action-stack">
                                <form method="POST" action="{{ route('admin.transactions.status', $trx->id) }}" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="status" value="success">
                                    <button type="submit" class="btn btn-sm btn-success">Konfirmasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.transactions.status', $trx->id) }}" class="inline-form"
                                      onsubmit="return confirm('Tandai transaksi ini gagal? Stok akan dikembalikan.');">
                                    @csrf
                                    <input type="hidden" name="status" value="failed">
                                    <input type="text" name="failure_reason" class="input-sm" placeholder="Alasan (opsional)" maxlength="255" aria-label="Alasan gagal">
                                    <button type="submit" class="btn btn-sm btn-danger">Gagal</button>
                                </form>
                                <a href="{{ route('admin.transactions.show', $trx->id) }}" class="btn btn-sm btn-ghost">Detail</a>
                            </div>
                        @elseif($trx->canBeReopened())
                            <a href="{{ route('admin.transactions.show', $trx->id) }}" class="btn btn-sm btn-outline">Detail</a>
                        @else
                            <a href="{{ route('admin.transactions.show', $trx->id) }}" class="btn btn-sm btn-ghost">Detail</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center muted">Tidak ada transaksi yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $transactions->links() }}
    </div>
</section>
@endsection
