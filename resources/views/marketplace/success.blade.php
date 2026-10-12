@extends('layouts.app')

@php
    $status = $order->status;
    $isPending = $status === 'pending';
    $isSuccess = $status === 'success';
    $isCancelled = $status === 'cancelled';
    $badge = $isSuccess ? ['badge-success', 'Berhasil'] : ($isPending ? ['badge-pending', 'Menunggu Pembayaran'] : ($isCancelled ? ['badge-cancelled', 'Dibatalkan'] : ['badge-failed', 'Gagal']));
@endphp

@section('title', ($isSuccess ? 'Pesanan Berhasil' : 'Pembayaran Pesanan') . ' - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div style="max-width:760px; margin:0 auto;">

            <div class="steps">
                <div class="step done">Keranjang</div>
                <div class="step {{ $isPending ? 'current' : 'done' }}">Pembayaran</div>
                <div class="step {{ $isSuccess ? 'done' : '' }}">{{ $isCancelled ? 'Dibatalkan' : 'Selesai' }}</div>
            </div>

            @if($isPending)
                <div class="card pay-panel">
                    <span class="badge badge-pending">Menunggu Pembayaran</span>
                    <h1 style="font-size:24px; margin:14px 0 4px;">Selesaikan Pembayaran</h1>
                    <p class="muted">Scan QRIS di bawah atau bayar sesuai kode, lalu tekan <strong>Sudah Bayar</strong>.</p>

                    <div class="qr-box">
                        @include('partials.fake-qr', ['code' => $order->order_code])
                    </div>

                    <div class="pay-amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                    <p class="muted" style="margin-top:6px;">Metode: <strong>{{ $order->payment_method }}</strong></p>

                    <div class="pay-code mt-16">{{ $order->order_code }}</div>

                    @if($order->payment_url)
                        <div class="mt-16">
                            <a href="{{ $order->payment_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-block">
                                Buka Halaman Pembayaran ({{ $order->payment_provider }})
                            </a>
                        </div>
                    @endif

                    <div class="mt-24">
                        <form action="{{ route('marketplace.payment.confirm', $order->order_code) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">@include('partials.icon', ['name' => 'check', 'size' => 16]) Sudah Bayar</button>
                        </form>
                        <form action="{{ route('marketplace.payment.cancel', $order->order_code) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Batalkan pesanan ini? Stok dikembalikan ke penjual.');">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-lg">Batalkan</button>
                        </form>
                    </div>
                </div>
            @elseif($isSuccess)
                <div class="card">
                    <div class="text-center">
                        <span class="badge badge-success">Pesanan Berhasil</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Terima Kasih!</h1>
                        <p class="muted">Pesanan marketplace kamu sedang diproses penjual. Cek statusnya di halaman Pesanan Saya.</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="text-center">
                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Pesanan {{ $isCancelled ? 'Dibatalkan' : 'Gagal' }}</h1>
                        <p class="muted">
                            @if($isCancelled)
                                Pesanan dibatalkan dan stok sudah dikembalikan ke penjual.
                            @else
                                Pembayaran tidak berhasil diproses. Silakan coba lagi.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="card mt-16">
                <h3>Detail Pesanan</h3>
                <div class="summary-row"><span>Kode Order</span><span class="mono">{{ $order->order_code }}</span></div>
                <div class="summary-row"><span>Pembeli</span><span>{{ $order->buyer->name ?? '-' }}</span></div>
                <div class="summary-row"><span>Metode</span><span>{{ $order->payment_method }}</span></div>
                <div class="summary-row"><span>Tanggal</span><span>{{ $order->created_at->format('d-m-Y H:i') }}</span></div>
                <div class="summary-row"><span>Status</span><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span></div>

                <h3 class="mt-24">Daftar Item</h3>
                <div class="order-items">
                    @foreach($order->items as $item)
                        <div class="order-item-row">
                            <span>{{ $item->product->name ?? '-' }} &times; {{ $item->quantity }}</span>
                            <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="summary-total">
                    <span>Total</span>
                    <span class="amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="mt-24">
                <a href="{{ route('marketplace.index') }}" class="btn btn-primary">Belanja Lagi</a>
                <a href="{{ route('orders.index') }}" class="btn btn-outline">Pesanan Saya</a>
            </div>
        </div>
    </div>
</section>
@endsection
