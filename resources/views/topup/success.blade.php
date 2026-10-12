@extends('layouts.app')

@php
    $status = $transaction->status;
    $isPending = $status === 'pending';
    $isSuccess = $status === 'success';
    $isCancelled = $status === 'cancelled';
    $badge = $isSuccess ? ['badge-success', 'Berhasil'] : ($isPending ? ['badge-pending', 'Menunggu Pembayaran'] : ($isCancelled ? ['badge-cancelled', 'Dibatalkan'] : ['badge-failed', 'Gagal']));
@endphp

@section('title', ($isSuccess ? 'Pembayaran Berhasil' : 'Pembayaran') . ' - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div style="max-width:760px; margin:0 auto;">

            <div class="steps">
                <div class="step done">Data &amp; Nominal</div>
                <div class="step {{ $isPending ? 'current' : 'done' }}">Pembayaran</div>
                <div class="step {{ $isSuccess ? 'done' : '' }}">
                    {{ $isCancelled ? 'Dibatalkan' : 'Selesai' }}
                </div>
            </div>

            @if($isPending)
                <div class="card pay-panel">
                    <span class="badge badge-pending">Menunggu Pembayaran</span>
                    <h1 style="font-size:24px; margin:14px 0 4px;">Selesaikan Pembayaran</h1>
                    <p class="muted">Scan QRIS di bawah atau bayar sesuai kode, lalu tekan <strong>Sudah Bayar</strong>.</p>

                    <div class="qr-box">
                        @include('partials.fake-qr', ['code' => $transaction->transaction_code])
                    </div>

                    <div class="pay-amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</div>
                    <p class="muted" style="margin-top:6px;">Metode: <strong>{{ $transaction->payment_method }}</strong></p>

                    <div class="pay-code mt-16">{{ $transaction->transaction_code }}</div>

                    @if($transaction->payment_url)
                        <div class="mt-16">
                            <a href="{{ $transaction->payment_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-block">
                                Buka Halaman Pembayaran ({{ $transaction->payment_provider }})
                            </a>
                        </div>
                    @endif

                    <div class="mt-24">
                        <form action="{{ route('topup.payment.confirm', $transaction->transaction_code) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">@include('partials.icon', ['name' => 'check', 'size' => 16]) Sudah Bayar</button>
                        </form>
                        <form action="{{ route('topup.payment.cancel', $transaction->transaction_code) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Batalkan pesanan ini? Stok akan dikembalikan.');">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-lg">Batalkan</button>
                        </form>
                    </div>
                </div>
            @elseif($isSuccess)
                <div class="card">
                    <div class="text-center">
                        <span class="badge badge-success">Pembayaran Berhasil</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Top Up Diproses</h1>
                        <p class="muted">Terima kasih! Pesanan top up kamu sudah masuk antrian proses.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="text-center">
                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Pesanan {{ $isCancelled ? 'Dibatalkan' : 'Gagal' }}</h1>
                        <p class="muted">
                            @if($isCancelled)
                                Pesanan ini sudah dibatalkan dan stok dikembalikan. Silakan buat pesanan baru bila masih berminat.
                            @else
                                Pembayaran tidak berhasil diproses. Silakan coba lagi atau gunakan metode lain.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="card mt-16">
                <h3>Detail Transaksi</h3>
                <div class="summary-row"><span>Kode Transaksi</span><span class="mono">{{ $transaction->transaction_code }}</span></div>
                <div class="summary-row"><span>Game</span><span>{{ $transaction->gameTitle->name ?? '-' }}</span></div>
                <div class="summary-row"><span>Produk</span><span>{{ $transaction->product->name ?? '-' }}</span></div>
                <div class="summary-row"><span>User ID</span><span>{{ $transaction->user_game_id }}</span></div>
                <div class="summary-row"><span>Server ID</span><span>{{ $transaction->server_id ?: '-' }}</span></div>
                <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                <div class="summary-row"><span>Tanggal</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                <div class="summary-row"><span>Status</span><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span></div>
                <div class="summary-total">
                    <span>Total</span>
                    <span class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="mt-24">
                <a href="{{ route('topup.index') }}" class="btn btn-primary">Top Up Lagi</a>
                @auth
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline">Riwayat Transaksi</a>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
