@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_code . ' - FastTopup Store')

@php
    $badge = [$order->statusBadge(), $order->statusLabel()];
@endphp

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('orders.index') }}">Pesanan Saya</a> / {{ $order->order_code }}
        </p>

        <div class="detail-grid">
            <div>
                <div class="card">
                    <div class="order-head">
                        <div>
                            <h1 style="font-size:22px; margin:0;">Detail Pesanan</h1>
                            <div class="order-code muted">{{ $order->order_code }}</div>
                        </div>
                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </div>

                    <div class="order-items mt-16">
                        <table class="table" style="min-width:0;">
                            <thead>
                                <tr>
                                    <th scope="col">Produk</th>
                                    <th scope="col" class="text-right">Harga</th>
                                    <th scope="col" class="text-right">Jumlah</th>
                                    <th scope="col" class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="wrap">
                                            <div class="item-cell">
                                                @include('partials.marketplace-image', ['product' => $item->product, 'variant' => 'is-thumb'])
                                                <span class="item-name">{{ $item->product->name ?? 'Produk Dihapus' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                        <td class="text-right">{{ $item->quantity }}</td>
                                        <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('partials.promo-summary', [
                        'promoCode' => $order->promo_code,
                        'discount'  => $order->discount_amount,
                    ])

                    <div class="summary-total">
                        <span>Total</span>
                        <span class="amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="card mt-16" id="ulasan">
                    <h3>Ulasan Produk</h3>

                    @if(! $order->isSuccess())
                        <p class="muted mb-0">
                            Ulasan bisa ditulis setelah pesanan ini berstatus Selesai.
                        </p>
                    @else
                        <p class="muted">
                            Bagikan pengalamanmu agar pembeli lain tidak menebak-nebak.
                            Satu ulasan per produk, bisa kamu perbarui kapan saja.
                        </p>

                        <div class="review-cta-list">
                            @foreach($order->items as $item)
                                @php
                                    $reviewed = $myReviews[$item->marketplace_product_id] ?? null;
                                @endphp
                                <div class="review-cta">
                                    <div class="review-cta-info">
                                        <span class="review-cta-name">{{ $item->product->name ?? 'Produk Dihapus' }}</span>
                                        @if($reviewed)
                                            <span class="review-cta-status">
                                                @include('partials.rating-stars', ['rating' => $reviewed->rating])
                                                <span class="muted">Ulasanmu sudah terkirim</span>
                                            </span>
                                        @else
                                            <span class="muted">Belum diulas</span>
                                        @endif
                                    </div>

                                    @if($item->product)
                                        <a href="{{ route('marketplace.show', $item->product->slug) }}#ulasan"
                                           class="btn btn-sm {{ $reviewed ? 'btn-ghost' : 'btn-primary' }}">
                                            {{ $reviewed ? 'Perbarui Ulasan' : 'Beri Ulasan' }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-16 actions">
                    @if($order->status === 'pending')
                        <a href="{{ route('marketplace.success', $order->order_code) }}" class="btn btn-success">
                            {{ $order->isPaymentClaimed() ? 'Lihat Status Pembayaran' : 'Bayar Sekarang' }}
                        </a>
                    @endif
                    <a href="{{ route('orders.index') }}" class="btn btn-ghost">Kembali</a>
                </div>
            </div>

            <div>
                <div class="card">
                    <h3>Info Transaksi</h3>
                    <div class="summary-row"><span>Kode Order</span><span class="mono">{{ $order->order_code }}</span></div>
                    <div class="summary-row"><span>Pembeli</span><span>{{ $order->buyer->name ?? '-' }}</span></div>
                    <div class="summary-row"><span>Metode Pembayaran</span><span>{{ $order->payment_method }}</span></div>
                    <div class="summary-row"><span>Jumlah Item</span><span>{{ $order->items->count() }}</span></div>
                    <div class="summary-row"><span>Tanggal Dibuat</span><span>{{ $order->created_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Terakhir Diperbarui</span><span>{{ $order->updated_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Status</span><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span></div>
                    @if($order->hasPaymentDeadline() && ! $order->isSuccess())
                        <div class="summary-row"><span>Batas Bayar</span><span>{{ $order->expires_at->format('d-m-Y H:i') }} WIB</span></div>
                    @endif
                    @if($order->confirmed_at)
                        <div class="summary-row"><span>Diverifikasi</span><span>{{ $order->confirmationLabel() }} &middot; {{ $order->confirmed_at->format('d-m-Y H:i') }}</span></div>
                    @endif
                    @if($order->failure_reason)
                        <div class="summary-row"><span>Alasan Gagal</span><span>{{ $order->failure_reason }}</span></div>
                    @endif
                    @if($order->hasPaymentProof())
                        <div class="summary-row"><span>Bukti Bayar</span><span><a href="{{ $order->paymentProofUrl() }}" target="_blank" rel="noopener">Lihat bukti</a></span></div>
                    @endif
                    <div class="summary-row"><span>Invoice</span><span><a href="{{ route('invoice.show', $order->order_code) }}">Lihat invoice</a></span></div>
                </div>

                <div class="card mt-16">
                    <h3>Jejak Verifikasi</h3>
                    <p class="muted">Alur pesananmu dari dibuat sampai diputuskan admin.</p>
                    @include('partials.verification-timeline', ['model' => $order])
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

