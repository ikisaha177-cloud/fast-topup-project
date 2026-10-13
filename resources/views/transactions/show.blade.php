@extends('layouts.app')

@php
    $status = $transaction->status;
    $badge = [$transaction->statusBadge(), $transaction->statusLabel()];
@endphp

@section('title', 'Detail Transaksi - GantinoID Store')

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('transactions.index') }}">Riwayat Transaksi</a> / {{ $transaction->transaction_code }}
        </p>

        @php
            $finalLabel = \App\Support\OrderStatus::finalStepLabel($status);
        @endphp

        <div class="steps">
            <div class="step done">Data &amp; Nominal</div>
            <div class="step {{ $status === 'pending' ? 'current' : ($transaction->isPaymentClaimed() || $status === 'success' ? 'done' : '') }}">Pembayaran</div>
            <div class="step {{ $status === 'success' ? 'done' : '' }}">{{ $finalLabel }}</div>
        </div>

        <div class="detail-grid">
            <div>
                <div class="card">
                    <div class="flex-between">
                        <h1 style="font-size:22px;" class="mb-0">Detail Transaksi</h1>
                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </div>

                    <div class="item-cell mt-16" style="margin-bottom:16px;">
                        @include('partials.game-icon', ['game' => $transaction->gameTitle ?? (object) [], 'size' => 'xs'])
                        <span>
                            <span class="item-name">{{ $transaction->gameTitle->name ?? '-' }}</span>
                            <span class="item-sub">{{ $transaction->product->name ?? '-' }}</span>
                        </span>
                    </div>

                    <div class="summary-row"><span>Kode Transaksi</span><span class="mono">{{ $transaction->transaction_code }}</span></div>
                    <div class="summary-row"><span>User ID</span><span class="mono">{{ $transaction->user_game_id }}</span></div>
                    <div class="summary-row"><span>Server ID</span><span>{{ $transaction->server_id ?: '-' }}</span></div>
                    <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                    <div class="summary-row"><span>Tanggal</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                    @if($transaction->confirmed_at)
                        <div class="summary-row"><span>Diverifikasi</span><span>{{ $transaction->confirmationLabel() }} &middot; {{ $transaction->confirmed_at->format('d-m-Y H:i') }}</span></div>
                    @endif
                    @if($transaction->failure_reason)
                        <div class="summary-row"><span>Alasan Gagal</span><span>{{ $transaction->failure_reason }}</span></div>
                    @endif
                    @if($transaction->hasPaymentProof())
                        <div class="summary-row"><span>Bukti Bayar</span><span><a href="{{ $transaction->paymentProofUrl() }}" target="_blank" rel="noopener">Lihat bukti</a></span></div>
                    @endif
                    <div class="summary-row"><span>Invoice</span><span><a href="{{ route('invoice.show', $transaction->transaction_code) }}">Lihat invoice</a></span></div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="card mt-16">
                    <h3>Jejak Verifikasi</h3>
                    <p class="muted">Alur pesananmu dari dibuat sampai diputuskan admin.</p>
                    @include('partials.verification-timeline', ['model' => $transaction])
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
                    <p class="muted">
                        @if($status === 'success')
                            Transaksi sudah selesai diproses.
                        @elseif($status === 'failed')
                            Transaksi ini gagal diproses dan stok sudah dikembalikan.
                        @else
                            Transaksi ini dibatalkan dan stok sudah dikembalikan.
                        @endif
                    </p>
                @endif
                <a href="{{ route('transactions.index') }}" class="btn btn-ghost btn-block mt-16">Kembali ke Riwayat</a>
                <a href="{{ route('topup.index') }}" class="btn btn-outline btn-block mt-16">Top Up Lagi</a>
            </div>
        </div>
    </div>
</section>
@endsection
