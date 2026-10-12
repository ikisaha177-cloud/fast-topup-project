@extends('layouts.app')

@section('title', 'Pesanan Saya - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Pesanan Saya</h1>
                <p class="section-subtitle mb-0">Riwayat pesanan marketplace milik akunmu.</p>
            </div>
        </div>

        @include('partials.account-tabs')

        <div class="chips" style="margin-bottom:20px;">
            <a href="{{ route('orders.index') }}" class="chip {{ $status ? '' : 'active' }}">Semua</a>
            <a href="{{ route('orders.index', ['status' => 'pending']) }}" class="chip {{ $status === 'pending' ? 'active' : '' }}">Menunggu Bayar</a>
            <a href="{{ route('orders.index', ['status' => 'success']) }}" class="chip {{ $status === 'success' ? 'active' : '' }}">Berhasil</a>
            <a href="{{ route('orders.index', ['status' => 'cancelled']) }}" class="chip {{ $status === 'cancelled' ? 'active' : '' }}">Dibatalkan</a>
        </div>

        @if($orders->isEmpty())
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'cart', 'size' => 34])</span>
                Belum ada pesanan.
                <div class="mt-16">
                    <a href="{{ route('marketplace.index') }}" class="btn btn-primary">Belanja Sekarang</a>
                </div>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:14px;">
                @foreach($orders as $order)
                    @php
                        $badge = $order->status === 'success'
                            ? ['badge-success', 'Berhasil']
                            : ($order->status === 'pending' ? ['badge-pending', 'Menunggu Bayar'] : ($order->status === 'cancelled' ? ['badge-cancelled', 'Dibatalkan'] : ['badge-failed', 'Gagal']));
                    @endphp

                    <div class="card card-hover order-card">
                        <div class="order-head">
                            <div>
                                <div class="order-code">{{ $order->order_code }}</div>
                                <div class="muted">{{ $order->created_at->format('d-m-Y H:i') }} &middot; {{ $order->payment_method }} &middot; {{ $order->items_count }} item</div>
                            </div>
                            <div class="actions">
                                <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                                @if($order->status === 'pending')
                                    <a href="{{ route('marketplace.success', $order->order_code) }}" class="btn btn-success btn-sm">Bayar Sekarang</a>
                                @endif
                                <a href="{{ route('orders.show', $order->order_code) }}" class="btn btn-outline btn-sm">Detail</a>
                            </div>
                        </div>

                        <div class="flex-between">
                            <span class="muted">Total</span>
                            <strong class="product-price">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $orders->links() }}
        @endif
    </div>
</section>
@endsection
