@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_code . ' - Admin')

@php
    $status = $order->status;
    $badge  = [$order->statusBadge(), $order->statusLabel()];
@endphp

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('admin.orders.index') }}">Pesanan Marketplace</a> / {{ $order->order_code }}
        </p>

        <div class="page-head">
            <div>
                <h1 class="section-title">Detail Pesanan</h1>
                <p class="section-subtitle mb-0">
                    <span class="mono">{{ $order->order_code }}</span> &middot;
                    <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                </p>
            </div>
            <div class="actions">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost">Kembali</a>
            </div>
        </div>

        @include('partials.admin-nav')

        <div class="detail-grid">
            <div>
                <div class="card">
                    <div class="order-head">
                        <div>
                            <h3 class="mb-0">Item Pesanan</h3>
                            <div class="muted" style="font-size:13px;">{{ $order->items->count() }} item</div>
                        </div>
                    </div>

                    <div class="order-items mt-16">
                        <table class="table" style="min-width:0;">
                            <thead>
                                <tr>
                                    <th scope="col">Produk</th>
                                    <th scope="col" class="text-right">Harga</th>
                                    <th scope="col" class="text-right">Qty</th>
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

                    <div class="summary-total">
                        <span>Total</span>
                        <span class="amount">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="card mt-16">
                    <h3>Data Pembeli</h3>
                    <div class="summary-row"><span>Nama</span><span>{{ $order->buyer->name ?? '-' }}</span></div>
                    <div class="summary-row"><span>Email</span><span>{{ $order->buyer->email ?? '-' }}</span></div>
                    <div class="summary-row"><span>Metode</span><span>{{ $order->payment_method }}</span></div>
                    <div class="summary-row"><span>Provider</span><span>{{ $order->payment_provider ?: 'Simulasi' }}</span></div>
                    <div class="summary-row"><span>Referensi</span><span class="mono">{{ $order->payment_reference ?: '-' }}</span></div>
                    <div class="summary-row"><span>Dibuat</span><span>{{ $order->created_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Diperbarui</span><span>{{ $order->updated_at->format('d-m-Y H:i') }}</span></div>
                </div>

                <div class="card mt-16">
                    <h3>Jejak Verifikasi</h3>
                    <div class="summary-row"><span>Pembeli Tandai Bayar</span><span>{{ $order->payment_claimed_at ? $order->payment_claimed_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    <div class="summary-row"><span>Pembayaran Diterima</span><span>{{ $order->paid_at ? $order->paid_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    <div class="summary-row"><span>Diverifikasi</span><span>{{ $order->confirmed_at ? $order->confirmationLabel() . ' · ' . $order->confirmed_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    @if($order->failure_reason)
                        <div class="summary-row"><span>Alasan Gagal</span><span>{{ $order->failure_reason }}</span></div>
                    @endif
                </div>

                @if($order->hasPaymentProof())
                    <div class="card mt-16">
                        <h3>Bukti Pembayaran</h3>
                        <p class="muted mb-0">Diunggah pembeli saat menandai sudah bayar.</p>
                        <a class="proof-preview mt-16" href="{{ $order->paymentProofUrl() }}" target="_blank" rel="noopener">
                            <img src="{{ $order->paymentProofUrl() }}" alt="Bukti pembayaran {{ $order->order_code }}">
                        </a>
                    </div>
                @endif

                <div class="card mt-16">
                    <h3>Riwayat Aktivitas</h3>
                    @if($activity->isEmpty())
                        <div class="empty">Belum ada aktivitas tercatat untuk pesanan ini.</div>
                    @else
                        <ul class="timeline">
                            @foreach($activity as $log)
                                <li class="timeline-item {{ $loop->first ? '' : 'is-muted' }}">
                                    <span class="tl-time">{{ $log->created_at->format('d-m-Y H:i') }}</span>
                                    <span class="tl-body">{{ $log->description }}</span>
                                    <span class="tl-actor">oleh {{ $log->user->name ?? 'Sistem' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card summary-card">
                <h3>Verifikasi</h3>

                @if($status === 'pending')
                    <p class="muted">
                        @if($order->isPaymentClaimed())
                            Pembeli sudah menandai pembayaran. Verifikasi lalu pilih hasilnya.
                        @else
                            Pembeli belum menandai pembayaran. Pastikan dana masuk sebelum menyetujui.
                        @endif
                    </p>

                    <div class="verify-panel mt-16">
                        <div class="verify-actions">
                            <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="status" value="success">
                                <button type="submit" class="btn btn-success btn-block">Konfirmasi &amp; Selesaikan</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" class="inline-form"
                              onsubmit="return confirm('Tandai pesanan ini gagal? Stok akan dikembalikan ke penjual.');">
                            @csrf
                            <input type="hidden" name="status" value="failed">
                            <input type="text" name="failure_reason" class="input-sm" placeholder="Alasan gagal (opsional)" maxlength="255" aria-label="Alasan gagal">
                            <button type="submit" class="btn btn-danger">Tandai Gagal</button>
                        </form>
                    </div>
                @elseif($order->canBeReopened())
                    <p class="muted">
                        Pesanan ini berstatus <strong>{{ $badge[1] }}</strong>. Bila ini salah klik,
                        buka kembali agar dapat diverifikasi ulang. Stok tiap item akan dikunci ulang.
                    </p>
                    <form method="POST" action="{{ route('admin.orders.reopen', $order->id) }}" class="mt-16"
                          onsubmit="return confirm('Buka kembali pesanan ini? Stok tiap item akan dikunci ulang dan status kembali menunggu konfirmasi.');">
                        @csrf
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-outline btn-block">
                            @include('partials.icon', ['name' => 'rotate', 'size' => 16]) Buka Kembali
                        </button>
                    </form>
                @else
                    <p class="muted">Pesanan sudah selesai dan bersifat final. Tidak ada tindakan lanjutan.</p>
                @endif

                <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost btn-block mt-16">Lihat Laporan</a>
            </div>
        </div>
    </div>
</section>
@endsection
