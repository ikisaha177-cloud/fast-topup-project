@extends('layouts.app')

@section('title', 'Pesanan Marketplace - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Pesanan Marketplace</h1>
                <p class="section-subtitle mb-0">Konfirmasi pesanan pembeli. Pesanan berstatus <strong>Selesai</strong> setelah kamu setujui.</p>
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
            <span class="stat-label">Total Pesanan</span>
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
            <span class="stat-label">Pendapatan Marketplace</span>
        </div>
    </div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="searchbar">
        <input type="text" name="q" aria-label="Cari pesanan" value="{{ $search }}" placeholder="Cari kode order atau nama/email pembeli…">
    </form>
    <div class="chips">
        <a href="{{ route('admin.orders.index') }}" class="chip {{ $status ? '' : 'active' }}" {!! aria_current(! $status, 'true') !!}>Semua</a>
        @foreach($statusOptions as $value => $text)
            <a href="{{ route('admin.orders.index', ['status' => $value]) }}" class="chip {{ $status === $value ? 'active' : '' }}" {!! aria_current($status === $value, 'true') !!}>{{ $text }}</a>
        @endforeach
    </div>
</div>

<div class="card table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Kode Order</th>
                <th scope="col">Pembeli</th>
                <th scope="col">Item</th>
                <th scope="col">Total</th>
                <th scope="col">Metode</th>
                <th scope="col">Status</th>
                <th scope="col">Pembayaran</th>
                <th scope="col">Tanggal</th>
                <th scope="col">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr class="{{ $order->needsReview() ? 'row-highlight' : '' }}">
                    <td>
                        <a class="cell-link mono" href="{{ route('admin.orders.show', $order->id) }}"
                           title="Buka detail pesanan">{{ $order->order_code }}</a>
                    </td>
                    <td>{{ $order->buyer->name ?? 'Guest' }}</td>
                    <td>
                        @php $firstItem = $order->items->first(); @endphp
                        <div class="item-cell">
                            @include('partials.marketplace-image', ['product' => $firstItem->product ?? (object) [], 'variant' => 'is-thumb'])
                            <span>
                                <span class="item-name">{{ $order->items_count }} item</span>
                                <span class="item-sub">{{ $firstItem->product->name ?? '-' }}@if($order->items_count > 1) +{{ $order->items_count - 1 }} lainnya @endif</span>
                            </span>
                        </div>
                    </td>
                    <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                    <td>{{ $order->payment_method }}</td>
                    <td>
                        <span class="badge {{ $badge($order->status) }}">{{ $label($order->status) }}</span>
                        @if($order->status === 'failed' && $order->failure_reason)
                            <div class="muted" style="font-size:12px;">{{ $order->failure_reason }}</div>
                        @endif
                        @if($order->confirmed_at)
                            <div class="muted" style="font-size:12px;">{{ $order->confirmationLabel() }}</div>
                        @endif
                    </td>
                    <td>
                        @if($order->isPaymentClaimed())
                            <span class="badge badge-info">Sudah Bayar</span>
                        @else
                            <span class="muted">Belum</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                        @if($order->status === 'pending')
                            <div class="action-stack">
                                <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="inline-form">
                                    @csrf
                                    <input type="hidden" name="status" value="success">
                                    <button type="submit" class="btn btn-sm btn-success">Konfirmasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="inline-form"
                                      onsubmit="return confirm('Tandai pesanan ini gagal? Stok akan dikembalikan ke penjual.');">
                                    @csrf
                                    <input type="hidden" name="status" value="failed">
                                    <input type="text" name="failure_reason" class="input-sm" placeholder="Alasan (opsional)" maxlength="255" aria-label="Alasan gagal">
                                    <button type="submit" class="btn btn-sm btn-danger">Gagal</button>
                                </form>
                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-ghost">Detail</a>
                            </div>
                        @elseif($order->canBeReopened())
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline">Detail</a>
                        @else
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-ghost">Detail</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center muted">Tidak ada pesanan yang cocok.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}
    </div>
</section>
@endsection
