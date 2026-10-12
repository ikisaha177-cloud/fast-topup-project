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
                    <div class="summary-row"><span>Game</span><span>{{ $transaction->gameTitle->name ?? '-' }}</span></div>
                    <div class="summary-row"><span>Produk</span><span>{{ $transaction->product->name ?? '-' }}</span></div>
                    <div class="summary-row"><span>Metode</span><span>{{ $transaction->payment_method }}</span></div>
                    <div class="summary-row"><span>Provider</span><span>{{ $transaction->payment_provider ?: 'Simulasi' }}</span></div>
                    <div class="summary-row"><span>Referensi</span><span class="mono">{{ $transaction->payment_reference ?: '-' }}</span></div>
                    <div class="summary-row"><span>Dibuat</span><span>{{ $transaction->created_at->format('d-m-Y H:i') }}</span></div>
                    <div class="summary-row"><span>Diperbarui</span><span>{{ $transaction->updated_at->format('d-m-Y H:i') }}</span></div>
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
                </div>

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
                @else
                    <p class="muted">Transaksi sudah selesai dan bersifat final. Tidak ada tindakan lanjutan.</p>
                @endif

                <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost btn-block mt-16">Lihat Laporan</a>
            </div>
        </div>
    </div>
</section>
@endsection
