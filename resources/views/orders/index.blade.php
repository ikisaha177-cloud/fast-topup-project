@extends('layouts.app')

@section('title', 'Pesanan Saya - FastTopup Store')

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
            <a href="{{ route('orders.index') }}" class="chip {{ $status ? '' : 'active' }}" {!! aria_current(! $status, 'true') !!}>Semua</a>
            <a href="{{ route('orders.index', ['status' => 'pending']) }}" class="chip {{ $status === 'pending' ? 'active' : '' }}" {!! aria_current($status === 'pending', 'true') !!}>Menunggu Konfirmasi</a>
            <a href="{{ route('orders.index', ['status' => 'success']) }}" class="chip {{ $status === 'success' ? 'active' : '' }}" {!! aria_current($status === 'success', 'true') !!}>Selesai</a>
            <a href="{{ route('orders.index', ['status' => 'failed']) }}" class="chip {{ $status === 'failed' ? 'active' : '' }}" {!! aria_current($status === 'failed', 'true') !!}>Gagal</a>
            <a href="{{ route('orders.index', ['status' => 'cancelled']) }}" class="chip {{ $status === 'cancelled' ? 'active' : '' }}" {!! aria_current($status === 'cancelled', 'true') !!}>Dibatalkan</a>
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
                        $badge = [$order->statusBadge(), $order->statusLabel()];
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
                                    <a href="{{ route('marketplace.success', $order->order_code) }}" class="btn btn-success btn-sm">
                                        {{ $order->isPaymentClaimed() ? 'Lihat Status' : 'Bayar Sekarang' }}
                                    </a>
                                @endif
                                <a href="{{ route('orders.show', $order->order_code) }}" class="btn btn-outline btn-sm">Detail</a>
                            </div>
                        </div>

                        <div class="order-thumbs">
                            @foreach($order->items->take(4) as $item)
                                @include('partials.marketplace-image', ['product' => $item->product, 'variant' => 'is-thumb'])
                            @endforeach
                            @if($order->items_count > 4)
                                <span class="order-thumb-more">+{{ $order->items_count - 4 }}</span>
                            @endif
                            <span class="muted">
                                {{ $order->items->pluck('product.name')->filter()->take(2)->implode(', ') }}
                                @if($order->items_count > 2) dan {{ $order->items_count - 2 }} lainnya @endif
                            </span>
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

