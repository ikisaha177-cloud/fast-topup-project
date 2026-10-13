@extends('layouts.app')

@php
    $status      = $transaction->status;
    $isPending   = $transaction->isPending();
    $isSuccess   = $transaction->isSuccess();
    $isCancelled = $status === 'cancelled';
    $finalLabel  = $isSuccess ? 'Selesai' : ($isCancelled ? 'Dibatalkan' : 'Gagal');
    $claimed     = $isPending && $transaction->isPaymentClaimed();
    $badge       = [$transaction->statusBadge(), $transaction->statusLabel()];
@endphp

@section('title', ($isSuccess ? 'Pesanan Selesai' : 'Status Pesanan') . ' - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div style="max-width:760px; margin:0 auto;">

            <div class="steps">
                <div class="step done">Data &amp; Nominal</div>
                <div class="step {{ $isPending ? 'current' : 'done' }}">Pembayaran</div>
                <div class="step {{ $isSuccess ? 'done' : '' }}">{{ $finalLabel }}</div>
            </div>

            @if($isPending)
                <div class="card pay-panel">
                    <span class="badge {{ $claimed ? 'badge-info' : 'badge-pending' }}">
                        {{ $claimed ? 'Menunggu Verifikasi Admin' : 'Menunggu Pembayaran' }}
                    </span>
                    <h1 style="font-size:24px; margin:14px 0 4px;">
                        {{ $claimed ? 'Pembayaran Sedang Diverifikasi' : 'Selesaikan Pembayaran' }}
                    </h1>
                    <p class="muted">
                        @if($claimed)
                            Kami sudah menerima penandaan pembayaranmu. Admin akan memverifikasi dan menyelesaikan pesanan ini secepatnya.
                        @else
                            Scan QRIS di bawah atau bayar sesuai kode, lalu tekan <strong>Saya Sudah Bayar</strong> agar admin dapat memverifikasi.
                        @endif
                    </p>

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
                        @unless($claimed)
                            <form action="{{ route('topup.payment.claim', $transaction->transaction_code) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">@include('partials.icon', ['name' => 'check', 'size' => 16]) Saya Sudah Bayar</button>
                            </form>
                        @endunless
                        <form action="{{ route('topup.payment.cancel', $transaction->transaction_code) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Batalkan pesanan ini? Stok akan dikembalikan.');">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-lg">Batalkan Pesanan</button>
                        </form>
                    </div>
                </div>
            @elseif($isSuccess)
                <div class="card">
                    <div class="text-center">
                        <span class="badge badge-success">Pesanan Selesai</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Top Up Selesai</h1>
                        <p class="muted">
                            @if($transaction->confirmedByGateway())
                                Pembayaranmu terverifikasi otomatis oleh penyedia pembayaran dan pesanan sudah selesai diproses.
                            @else
                                Terima kasih! Pesanan top up kamu sudah dikonfirmasi admin dan selesai diproses.
                            @endif
                        </p>
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
                                Pesanan ini tidak dapat dikonfirmasi oleh admin. Stok sudah dikembalikan; silakan buat pesanan baru.
                            @endif
                        </p>
                        @if(! $isCancelled && $transaction->failure_reason)
                            <p class="muted"><strong>Alasan:</strong> {{ $transaction->failure_reason }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card mt-16">
                <h3>Detail Transaksi</h3>
                <div class="item-cell" style="margin-bottom:14px;">
                    @include('partials.game-icon', ['game' => $transaction->gameTitle ?? (object) [], 'size' => 'xs'])
                    <span>
                        <span class="item-name">{{ $transaction->gameTitle->name ?? '-' }}</span>
                        <span class="item-sub">{{ $transaction->product->name ?? '-' }}</span>
                    </span>
                </div>
                <div class="summary-row"><span>Kode Transaksi</span><span class="mono">{{ $transaction->transaction_code }}</span></div>
                <div class="summary-row"><span>User ID</span><span>{{ $transaction->user_game_id }}</span></div>
                <div class="summary-row"><span>Server ID</span><span>{{ $transaction->server_id ?: '-' }}</span></div>
                <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                <div class="summary-row"><span>Tanggal</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                <div class="summary-row"><span>Status</span><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span></div>
                @if($transaction->confirmed_at)
                    <div class="summary-row"><span>Diverifikasi</span><span>{{ $transaction->confirmationLabel() }} &middot; {{ $transaction->confirmed_at->format('d-m-Y H:i') }}</span></div>
                @endif
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
