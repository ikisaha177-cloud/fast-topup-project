@extends('layouts.app')

@section('title', 'Dashboard Admin - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Dashboard Admin</h1>
                <p class="section-subtitle mb-0">Ringkasan performa toko hari ini.</p>
            </div>
            <div class="actions">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline">Laporan Penjualan</a>
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-primary">Konfirmasi Pesanan</a>
            </div>
        </div>

        @include('partials.admin-nav')

        <div class="kpi-row">
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['users'] }}</span>
                <span class="stat-label">User</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['games'] }}</span>
                <span class="stat-label">Game</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['products'] }}</span>
                <span class="stat-label">Produk Top Up</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number" style="color:var(--yellow);">{{ $stats['pending'] }}</span>
                <span class="stat-label">Menunggu Konfirmasi</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number" style="color:var(--brand);">{{ $stats['review'] }}</span>
                <span class="stat-label">Perlu Diverifikasi</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['marketplace_products'] }}</span>
                <span class="stat-label">Produk Marketplace</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['marketplace_orders'] }}</span>
                <span class="stat-label">Order Marketplace</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $stats['transactions'] }}</span>
                <span class="stat-label">Transaksi Top Up</span>
            </div>
            <div class="card stat-card is-accent">
                <span class="stat-number">Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</span>
                <span class="stat-label">Total Pendapatan</span>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <h3>Pendapatan 7 Hari</h3>
                <p class="muted">Gabungan top up + marketplace, per hari.</p>

                <div class="chart">
                    @foreach($revenueSeries as $day)
                        <div class="chart-col">
                            <span class="chart-value">
                                {{ $day['revenue'] > 0 ? 'Rp ' . number_format($day['revenue'] / 1000, 0, ',', '.') . 'k' : '—' }}
                            </span>
                            <div class="chart-bar" style="height:{{ max(4, round($day['revenue'] / $revenuePeak * 100)) }}%;"
                                 title="Rp {{ number_format($day['revenue'], 0, ',', '.') }}"></div>
                            <span class="chart-label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <h3>Transaksi Terbaru</h3>
                @if($latestTransactions->isEmpty())
                    <div class="empty">Belum ada transaksi.</div>
                @else
                    <div class="order-items">
                        @foreach($latestTransactions as $trx)
                            <div class="order-item-row">
                                <span>
                                    <span class="mono">{{ $trx->transaction_code }}</span><br>
                                    <span class="muted">{{ $trx->user->name ?? 'Guest' }} &middot; {{ $trx->gameTitle->name ?? '-' }}</span>
                                </span>
                                <span>
                                    <strong>Rp {{ number_format($trx->amount, 0, ',', '.') }}</strong><br>
                                    <span class="badge {{ $trx->statusBadge() }}">{{ $trx->statusLabel() }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-16">
                    <a href="{{ route('admin.transactions.index') }}" class="btn btn-outline btn-sm">Semua Transaksi</a>
                </div>
            </div>
        </div>

        <h2 class="section-title mt-24" style="font-size:20px;">Pesanan Marketplace Terbaru</h2>
        @if($latestOrders->isEmpty())
            <div class="empty">Belum ada pesanan marketplace.</div>
        @else
            <div class="card table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Kode Order</th>
                            <th scope="col">Pembeli</th>
                            <th scope="col">Total</th>
                            <th scope="col">Metode</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestOrders as $order)
                            <tr>
                                <td class="mono">{{ $order->order_code }}</td>
                                <td>{{ $order->buyer->name ?? 'Guest' }}</td>
                                <td>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td>{{ $order->payment_method }}</td>
                                <td>
                                    <span class="badge {{ $order->statusBadge() }}">{{ $order->statusLabel() }}</span>
                                </td>
                                <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection

