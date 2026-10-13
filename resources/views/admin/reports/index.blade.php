@extends('layouts.app')

@section('title', 'Laporan Penjualan - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Laporan Penjualan</h1>
                <p class="section-subtitle mb-0">Rekap harian, bulanan, dan tahunan untuk top up &amp; marketplace.</p>
            </div>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="btn btn-outline">
                @include('partials.icon', ['name' => 'receipt', 'size' => 16]) Unduh CSV
            </a>
        </div>

        @include('partials.admin-nav')

        {{-- Pemilih tipe laporan --}}
        <div class="chips" style="margin-bottom:16px;">
            <a href="{{ route('admin.reports.index', ['periode' => 'harian', 'tahun' => $year, 'bulan' => $month, 'status' => $status]) }}"
               class="chip {{ $type === 'harian' ? 'active' : '' }}">Harian</a>
            <a href="{{ route('admin.reports.index', ['periode' => 'bulanan', 'tahun' => $year, 'status' => $status]) }}"
               class="chip {{ $type === 'bulanan' ? 'active' : '' }}">Bulanan</a>
            <a href="{{ route('admin.reports.index', ['periode' => 'tahunan', 'tahun' => $year, 'status' => $status]) }}"
               class="chip {{ $type === 'tahunan' ? 'active' : '' }}">Tahunan</a>
        </div>

        {{-- Filter periode & status --}}
        <div class="card report-filter">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="filter-form">
                <input type="hidden" name="periode" value="{{ $type }}">

                @if($type === 'harian')
                    <label>
                        <span>Bulan</span>
                        <select name="bulan">
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $name)
                                <option value="{{ $i + 1 }}" {{ (int) $month === $i + 1 ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                @if($type !== 'tahunan')
                    <label>
                        <span>Tahun</span>
                        <select name="tahun">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </label>
                @endif

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>Semua Status</option>
                        @foreach($statusLabels as $value => $text)
                            <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
        </div>

        <p class="muted" style="margin:0 0 16px;">
            Periode: <strong>{{ $report['periodLabel'] }}</strong>
            @if($status !== 'semua')
                &middot; Filter: <strong>{{ $statusLabels[$status] ?? $status }}</strong>
            @endif
        </p>

        {{-- KPI ringkasan --}}
        <div class="kpi-row">
            <div class="card stat-card is-accent">
                <span class="stat-number">Rp {{ number_format($report['summary']['revenue'], 0, ',', '.') }}</span>
                <span class="stat-label">Total Pendapatan</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">Rp {{ number_format($report['summary']['topup_revenue'], 0, ',', '.') }}</span>
                <span class="stat-label">Pendapatan Top Up</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">Rp {{ number_format($report['summary']['market_revenue'], 0, ',', '.') }}</span>
                <span class="stat-label">Pendapatan Marketplace</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">{{ $report['summary']['total_count'] }}</span>
                <span class="stat-label">Total Pesanan</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number" style="color:var(--green);">{{ $report['summary']['success_count'] }}</span>
                <span class="stat-label">Selesai</span>
            </div>
            <div class="card stat-card">
                <span class="stat-number">Rp {{ number_format($report['summary']['average'], 0, ',', '.') }}</span>
                <span class="stat-label">Rata-rata / Pesanan</span>
            </div>
        </div>

        {{-- Grafik pendapatan --}}
        <div class="card">
            <h3>Grafik Pendapatan</h3>
            <p class="muted">Total pendapatan per {{ $type === 'harian' ? 'hari' : ($type === 'bulanan' ? 'bulan' : 'tahun') }} (hanya pesanan selesai).</p>

            @if($report['summary']['revenue'] === 0)
                <div class="empty">Belum ada pendapatan pada periode ini.</div>
            @else
                <div class="chart chart-scroll">
                    @foreach($report['rows'] as $row)
                        <div class="chart-col" title="Rp {{ number_format($row['revenue'], 0, ',', '.') }}">
                            <span class="chart-value">
                                {{ $row['revenue'] > 0 ? 'Rp ' . number_format($row['revenue'] / 1000, 0, ',', '.') . 'k' : '—' }}
                            </span>
                            <div class="chart-bar" style="height:{{ max(4, round($row['revenue'] / $report['peak'] * 100)) }}%;"></div>
                            <span class="chart-label">{{ $row['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Tabel rincian --}}
        <div class="card table-wrap mt-16">
            <h3>Rincian per Periode</h3>
            <p class="muted" style="margin:-6px 0 10px; font-size:13px;">Kolom jumlah menghitung semua pesanan; kolom pendapatan hanya pesanan berstatus <strong>Selesai</strong>.</p>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Periode</th>
                        <th scope="col" class="text-right">Transaksi Top Up</th>
                        <th scope="col" class="text-right">Pesanan Marketplace</th>
                        <th scope="col" class="text-right">Pendapatan Top Up</th>
                        <th scope="col" class="text-right">Pendapatan Marketplace</th>
                        <th scope="col" class="text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="text-right">{{ $row['topup_count'] }}</td>
                            <td class="text-right">{{ $row['order_count'] }}</td>
                            <td class="text-right">Rp {{ number_format($row['topup_revenue'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($row['market_revenue'], 0, ',', '.') }}</td>
                            <td class="text-right"><strong>Rp {{ number_format($row['revenue'], 0, ',', '.') }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center muted">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="col">Total</th>
                        <th scope="col" class="text-right">{{ $report['summary']['topup_count'] }}</th>
                        <th scope="col" class="text-right">{{ $report['summary']['order_count'] }}</th>
                        <th scope="col" class="text-right">Rp {{ number_format($report['summary']['topup_revenue'], 0, ',', '.') }}</th>
                        <th scope="col" class="text-right">Rp {{ number_format($report['summary']['market_revenue'], 0, ',', '.') }}</th>
                        <th scope="col" class="text-right">Rp {{ number_format($report['summary']['revenue'], 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Sebaran status (seluruh periode, tanpa filter) --}}
        <div class="card mt-16">
            <h3>Sebaran Status</h3>
            <p class="muted">Jumlah pesanan per status pada periode ini (tidak terpengaruh filter).</p>

            <div class="status-breakdown">
                @foreach($report['statusBreakdown'] as $value => $row)
                    <div class="status-cell">
                        <span class="badge {{ \App\Support\OrderStatus::badge($value) }}">{{ $row['label'] }}</span>
                        <span class="status-count">{{ $row['count'] }}</span>
                        <span class="muted">Rp {{ number_format($row['amount'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-2 mt-16">
            {{-- Game terlaris --}}
            <div class="card table-wrap">
                <h3>Game Terlaris</h3>
                <p class="muted" style="font-size:13px; margin:-6px 0 10px;">Hanya menghitung pesanan berstatus <strong>Selesai</strong>.</p>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Game</th>
                            <th scope="col" class="text-right">Terjual</th>
                            <th scope="col" class="text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['topGames'] as $game)
                            <tr>
                                <td>{{ $game['name'] }}</td>
                                <td class="text-right">{{ $game['count'] }}</td>
                                <td class="text-right">Rp {{ number_format($game['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Produk marketplace terlaris --}}
            <div class="card table-wrap">
                <h3>Produk Marketplace Terlaris</h3>
                <p class="muted" style="font-size:13px; margin:-6px 0 10px;">Hanya menghitung pesanan berstatus <strong>Selesai</strong>.</p>
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Produk</th>
                            <th scope="col" class="text-right">Terjual</th>
                            <th scope="col" class="text-right">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['topProducts'] as $product)
                            <tr>
                                <td class="wrap">{{ $product['name'] }}</td>
                                <td class="text-right">{{ $product['quantity'] }}</td>
                                <td class="text-right">Rp {{ number_format($product['revenue'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Metode pembayaran --}}
        <div class="card table-wrap mt-16">
            <h3>Metode Pembayaran</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Metode</th>
                        <th scope="col" class="text-right">Jumlah Pesanan</th>
                        <th scope="col" class="text-right">Pendapatan (Selesai)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['paymentMethods'] as $method)
                        <tr>
                            <td>{{ $method['method'] }}</td>
                            <td class="text-right">{{ $method['count'] }}</td>
                            <td class="text-right">Rp {{ number_format($method['revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center muted">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
