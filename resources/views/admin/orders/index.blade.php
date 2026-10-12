@extends('layouts.app')

@section('title', 'Pesanan Marketplace - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Pesanan Marketplace</h1>
                <p class="section-subtitle mb-0">Pantau dan verifikasi pesanan dari pembeli marketplace.</p>
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
            <span class="stat-label">Total Pesanan</span>
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
            <span class="stat-label">Pendapatan Marketplace</span>
        </div>
    </div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="searchbar">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode order atau nama/email pembeli…">
    </form>
    <div class="chips">
        <a href="{{ route('admin.orders.index') }}" class="chip {{ $status ? '' : 'active' }}">Semua</a>
        @foreach($statusOptions as $value => $text)
            <a href="{{ route('admin.orders.index', ['status' => $value]) }}" class="chip {{ $status === $value ? 'active' : '' }}">{{ $text }}</a>
        @endforeach
    </div>
</div>

<div class="card table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Kode Order</th>
                <th>Pembeli</th>
                <th>Item</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Tanggal</th>
                <th>Ubah Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="mono">{{ $order->order_code }}</td>
                    <td>{{ $order->buyer->name ?? 'Guest' }}</td>
                    <td>{{ $order->items_count }}</td>
                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td>{{ $order->payment_method }}</td>
                    <td><span class="badge {{ $badge($order->status) }}">{{ $label($order->status) }}</span></td>
                    <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="inline-form">
                            @csrf
                            <select name="status" aria-label="Ubah status">
                                @foreach($statusOptions as $value => $text)
                                    <option value="{{ $value }}" {{ $order->status === $value ? 'selected' : '' }}>{{ $text }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline">Simpan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center muted">Tidak ada pesanan yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}
    </div>
</section>
@endsection
