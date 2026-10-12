@extends('layouts.app')

@section('title', 'Data Transaksi - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Data Transaksi</h1>
                <p class="section-subtitle mb-0">Pantau dan verifikasi transaksi top up dari pembeli.</p>
            </div>
        </div>

        @include('partials.admin-nav')

@php
    $statusOptions = ['pending' => 'Pending', 'success' => 'Berhasil', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal'];
    $badge = fn($s) => $s === 'success' ? 'badge-success' : ($s === 'pending' ? 'badge-pending' : ($s === 'cancelled' ? 'badge-cancelled' : 'badge-failed'));
    $label = fn($s) => $statusOptions[$s] ?? $s;
@endphp

@if($summary)
    <div class="kpi-row">
        <div class="card stat-card">
            <span class="stat-number">{{ $summary['total'] }}</span>
            <span class="stat-label">Total Transaksi</span>
        </div>
        <div class="card stat-card">
            <span class="stat-number" style="color:var(--yellow);">{{ $summary['pending'] }}</span>
            <span class="stat-label">Pending</span>
        </div>
        <div class="card stat-card">
            <span class="stat-number" style="color:var(--green);">{{ $summary['success'] }}</span>
            <span class="stat-label">Berhasil</span>
        </div>
        <div class="card stat-card is-accent">
            <span class="stat-number">Rp {{ number_format($summary['revenue'], 0, ',', '.') }}</span>
            <span class="stat-label">Pendapatan Top Up</span>
        </div>
    </div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="searchbar">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode transaksi, User ID, atau nama/email pembeli…">
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
                <th>Kode</th>
                <th>User</th>
                <th>Game</th>
                <th>Produk</th>
                <th>User ID Game</th>
                <th>Metode</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Ubah Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
                <tr>
                    <td class="mono">{{ $trx->transaction_code }}</td>
                    <td>{{ $trx->user->name ?? 'Guest' }}</td>
                    <td>{{ $trx->gameTitle->name ?? '-' }}</td>
                    <td>{{ $trx->product->name ?? '-' }}</td>
                    <td>{{ $trx->user_game_id }}{{ $trx->server_id ? ' (' . $trx->server_id . ')' : '' }}</td>
                    <td>{{ $trx->payment_method }}</td>
                    <td>Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                    <td><span class="badge {{ $badge($trx->status) }}">{{ $label($trx->status) }}</span></td>
                    <td>{{ $trx->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.transactions.status', $trx->id) }}" class="inline-form">
                            @csrf
                            <select name="status" aria-label="Ubah status">
                                @foreach($statusOptions as $value => $text)
                                    <option value="{{ $value }}" {{ $trx->status === $value ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline">Simpan</button>
                        </form>
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
