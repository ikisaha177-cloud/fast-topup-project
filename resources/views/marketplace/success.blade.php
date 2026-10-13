@extends('layouts.app')

@php
    $status      = $order->status;
    $isPending   = $order->isPending();
    $isSuccess   = $order->isSuccess();
    $isCancelled = $status === 'cancelled';
    $finalLabel  = $isSuccess ? 'Selesai' : ($isCancelled ? 'Dibatalkan' : 'Gagal');
    $claimed     = $isPending && $order->isPaymentClaimed();
    $badge       = [$order->statusBadge(), $order->statusLabel()];
@endphp

@section('title', ($isSuccess ? 'Pesanan Selesai' : 'Status Pesanan') . ' - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div style="max-width:760px; margin:0 auto;">

            <div class="steps">
                <div class="step done">Keranjang</div>
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
                        @unless($claimed)
                            <form action="{{ route('marketplace.payment.claim', $order->order_code) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">@include('partials.icon', ['name' => 'check', 'size' => 16]) Saya Sudah Bayar</button>
                            </form>
                        @endunless
                        <form action="{{ route('marketplace.payment.cancel', $order->order_code) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Batalkan pesanan ini? Stok dikembalikan ke penjual.');">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-lg">Batalkan Pesanan</button>
                        </form>
                    </div>
                </div>
            @elseif($isSuccess)
                <div class="card">
                    <div class="text-center">
                        <span class="badge badge-success">Pesanan Selesai</span>
                        <h1 style="font-size:24px; margin:14px 0 4px;">Terima Kasih!</h1>
                        <p class="muted">
                            @if($order->confirmedByGateway())
                                Pembayaranmu terverifikasi otomatis oleh penyedia pembayaran dan pesanan sudah selesai. Cek detailnya di halaman Pesanan Saya.
                            @else
                                Pesanan marketplace kamu sudah dikonfirmasi admin dan selesai. Cek detailnya di halaman Pesanan Saya.
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
                                Pesanan dibatalkan dan stok sudah dikembalikan ke penjual.
                            @else
                                Pesanan ini tidak dapat dikonfirmasi oleh admin. Stok sudah dikembalikan ke penjual.
                            @endif
                        </p>
                        @if(! $isCancelled && $order->failure_reason)
                            <p class="muted"><strong>Alasan:</strong> {{ $order->failure_reason }}</p>
                        @endif
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
                @if($order->confirmed_at)
                    <div class="summary-row"><span>Diverifikasi</span><span>{{ $order->confirmationLabel() }} &middot; {{ $order->confirmed_at->format('d-m-Y H:i') }}</span></div>
                @endif

                <h3 class="mt-24">Daftar Item</h3>
                <div class="order-items">
                    @foreach($order->items as $item)
                        <div class="order-item-row">
                            <div class="item-cell">
                                @include('partials.marketplace-image', ['product' => $item->product, 'variant' => 'is-thumb'])
                                <span>{{ $item->product->name ?? 'Produk Dihapus' }} &times; {{ $item->quantity }}</span>
                            </div>
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
