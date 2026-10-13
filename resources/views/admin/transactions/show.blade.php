@extends('layouts.app')

@section('title', 'Detail Transaksi ' . $transaction->transaction_code . ' - Admin')

@php
    $status = $transaction->status;
    $badge  = [$transaction->statusBadge(), $transaction->statusLabel()];
@endphp

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('admin.transactions.index') }}">Data Transaksi</a> / {{ $transaction->transaction_code }}
        </p>

        <div class="page-head">
            <div>
                <h1 class="section-title">Detail Transaksi</h1>
                <p class="section-subtitle mb-0">
                    <span class="mono">{{ $transaction->transaction_code }}</span> &middot;
                    <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                </p>
            </div>
            <div class="actions">
                <a href="{{ route('admin.transactions.index') }}" class="btn btn-ghost">Kembali</a>
            </div>
        </div>

        @include('partials.admin-nav')

        <div class="detail-grid">
            <div>
                <div class="card">
                    <h3>Data Pembeli</h3>
                    <div class="summary-row"><span>Nama</span><span>{{ $transaction->user->name ?? 'Guest (tanpa akun)' }}</span></div>
                    <div class="summary-row"><span>Email</span><span>{{ $transaction->user->email ?? '-' }}</span></div>
                    <div class="summary-row"><span>User ID Game</span><span class="mono">{{ $transaction->user_game_id }}</span></div>
                    <div class="summary-row"><span>Server ID</span><span>{{ $transaction->server_id ?: '-' }}</span></div>
                </div>

                <div class="card mt-16">
                    <h3>Data Pesanan</h3>
                    <div class="item-cell" style="margin-bottom:14px;">
                        @include('partials.game-icon', ['game' => $transaction->gameTitle ?? (object) [], 'size' => 'xs'])
                        <span>
                            <span class="item-name">{{ $transaction->gameTitle->name ?? '-' }}</span>
                            <span class="item-sub">{{ $transaction->product->name ?? '-' }}</span>
                        </span>
                    </div>
                    <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                    <div class="summary-row"><span>Provider</span><span>{{ $transaction->payment_provider ?: 'Simulasi' }}</span></div>
                    <div class="summary-row"><span>Referensi</span><span class="mono">{{ $transaction->payment_reference ?: '-' }}</span></div>
                    <div class="summary-row"><span>Dibuat</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Diperbarui</span><span>{{ $transaction->updated_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Invoice</span><span><a href="{{ route('invoice.show', $transaction->transaction_code) }}" target="_blank" rel="noopener">Lihat invoice</a></span></div>
                    @include('partials.promo-summary', [
                        'promoCode' => $transaction->promo_code,
                        'discount'  => $transaction->discount_amount,
                    ])
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="card mt-16">
                    <h3>Jejak Verifikasi</h3>
                    <div class="summary-row"><span>Pembeli Tandai Bayar</span><span>{{ $transaction->payment_claimed_at ? $transaction->payment_claimed_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    <div class="summary-row"><span>Pembayaran Diterima</span><span>{{ $transaction->paid_at ? $transaction->paid_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    <div class="summary-row"><span>Diverifikasi</span><span>{{ $transaction->confirmed_at ? $transaction->confirmationLabel() . ' · ' . $transaction->confirmed_at->format('d-m-Y H:i') : 'Belum' }}</span></div>
                    @if($transaction->failure_reason)
                        <div class="summary-row"><span>Alasan Gagal</span><span>{{ $transaction->failure_reason }}</span></div>
                    @endif
                    @if($transaction->wasRevoked())
                        <div class="summary-row"><span>Konfirmasi Dibatalkan</span><span>{{ $transaction->revoked_at->format('d-m-Y H:i') }}@if($transaction->revokedBy) · {{ $transaction->revokedBy->name }}@endif</span></div>
                    @endif
                </div>

                @if($transaction->hasPaymentProof())
                    <div class="card mt-16">
                        <h3>Bukti Pembayaran</h3>
                        <p class="muted mb-0">Diunggah pembeli saat menandai sudah bayar.</p>
                        <a class="proof-preview mt-16" href="{{ $transaction->paymentProofUrl() }}" target="_blank" rel="noopener">
                            <img src="{{ $transaction->paymentProofUrl() }}" alt="Bukti pembayaran {{ $transaction->transaction_code }}">
                        </a>
                    </div>
                @endif

                <div class="card mt-16">
                    <h3>Riwayat Aktivitas</h3>
                    @if($activity->isEmpty())
                        <div class="empty">Belum ada aktivitas tercatat untuk transaksi ini.</div>
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
                        @if($transaction->isPaymentClaimed())
                            Pembeli sudah menandai pembayaran. Verifikasi lalu pilih hasilnya.
                        @else
                            Pembeli belum menandai pembayaran. Pastikan dana masuk sebelum menyetujui.
                        @endif
                    </p>

                    <div class="verify-panel mt-16">
                        <div class="verify-actions">
                            <form method="POST" action="{{ route('admin.transactions.status', $transaction->id) }}" class="inline-form">
                                @csrf
                                <input type="hidden" name="status" value="success">
                                <button type="submit" class="btn btn-success btn-block">Konfirmasi &amp; Selesaikan</button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route('admin.transactions.status', $transaction->id) }}" class="inline-form"
                              onsubmit="return confirm('Tandai transaksi ini gagal? Stok akan dikembalikan.');">
                            @csrf
                            <input type="hidden" name="status" value="failed">
                            <input type="text" name="failure_reason" class="input-sm" placeholder="Alasan gagal (opsional)" maxlength="255" aria-label="Alasan gagal">
                            <button type="submit" class="btn btn-danger">Tandai Gagal</button>
                        </form>
                    </div>
                @elseif($transaction->canBeReopened())
                    <p class="muted">
                        Transaksi ini berstatus <strong>{{ $badge[1] }}</strong>. Bila ini salah klik,
                        buka kembali agar dapat diverifikasi ulang. Stok akan dikunci ulang.
                    </p>
                    <form method="POST" action="{{ route('admin.transactions.reopen', $transaction->id) }}" class="mt-16"
                          onsubmit="return confirm('Buka kembali transaksi ini? Stok akan dikunci ulang dan status kembali menunggu konfirmasi.');">
                        @csrf
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-outline btn-block">
                            @include('partials.icon', ['name' => 'rotate', 'size' => 16]) Buka Kembali
                        </button>
                    </form>
                @elseif($transaction->canBeRevoked())
                    <p class="muted">
                        Transaksi ini sudah <strong>{{ $badge[1] }}</strong>. Bila salah dikonfirmasi,
                        batalkan konfirmasinya: status kembali menjadi <strong>Gagal</strong> dan stok
                        dikembalikan. Tindakan ini tercatat pada jejak audit.
                    </p>
                    <form method="POST" action="{{ route('admin.transactions.status', $transaction->id) }}" class="mt-16 verify-panel"
                          onsubmit="return confirm('Batalkan konfirmasi transaksi ini? Status menjadi Gagal dan stok dikembalikan.');">
                        @csrf
                        <input type="hidden" name="status" value="failed">
                        <input type="text" name="failure_reason" class="input-sm" placeholder="Alasan pembatalan (opsional)" maxlength="255" aria-label="Alasan pembatalan konfirmasi">
                        <button type="submit" class="btn btn-danger btn-block">
                            @include('partials.icon', ['name' => 'rotate', 'size' => 16]) Batalkan Konfirmasi
                        </button>
                    </form>
                @else
                    <p class="muted">Tidak ada tindakan lanjutan yang tersedia untuk transaksi ini.</p>
                @endif

                <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost btn-block mt-16">Lihat Laporan</a>
            </div>
        </div>
    </div>
</section>
@endsection
