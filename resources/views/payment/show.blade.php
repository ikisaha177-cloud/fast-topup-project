@extends('layouts.gateway')

@php
    /**
     * Portal pembayaran — satu halaman untuk tiga keadaan:
     *   1. "payable" : instruksi pembayaran (QRIS / e-wallet / transfer bank)
     *                  + tombol "Saya Sudah Bayar".
     *   2. "receipt" : struk pembayaran yang bisa di-screenshot pembeli
     *                  sebagai bukti (muncul setelah menandai sudah bayar,
     *                  atau setelah pesanan selesai).
     *   3. "closed"  : pesanan gagal/dibatalkan — tidak ada yang bisa dibayar.
     *
     * Data $doc & $channel disusun oleh PaymentPortalController.
     */
    $state   = $doc['state'];
    $paid    = $doc['paid'];
    $amount  = 'Rp ' . number_format($doc['amount'], 0, ',', '.');
    $stampAt = $doc['confirmed_at'] ?: $doc['created_at'];
@endphp

@section('title', ($state === 'receipt' ? 'Struk Pembayaran ' : 'Pembayaran ') . $doc['code'] . ' - GantinoID Pay')

@section('gateway_back')
    <a href="{{ $backUrl }}" class="gateway-back">&larr; Kembali ke halaman pesanan</a>
@endsection

@section('content')
<div class="portal">

    <header class="portal-head">
        <span class="portal-eyebrow">Portal Pembayaran</span>
        <h1>{{ $doc['kind'] }}</h1>
        <p class="portal-code">Kode Pesanan <span class="mono">{{ $doc['code'] }}</span></p>
    </header>

    @if($state !== 'closed')
        <div class="portal-amount">
            <span>{{ $paid ? 'Total Dibayar' : 'Total Tagihan' }}</span>
            <strong>{{ $amount }}</strong>
        </div>
    @endif

    @if($state === 'closed')
        {{-- ---------- Keadaan: tidak dapat dibayar ---------- --}}
        <div class="portal-card">
            <div class="portal-closed">
                <span class="badge {{ $doc['status'] === 'cancelled' ? 'badge-cancelled' : 'badge-failed' }}">{{ $doc['label'] }}</span>
                <h2>Pesanan Tidak Dapat Dibayar</h2>
                <p class="muted">
                    Pesanan ini sudah {{ strtolower($doc['label']) }}, jadi tidak ada pembayaran yang perlu diselesaikan.
                    Silakan buat pesanan baru bila masih berminat.
                </p>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-outline btn-block mt-16">Lihat Detail Pesanan</a>
            <a href="{{ route('home') }}" class="btn btn-ghost btn-block mt-8">Kembali ke Beranda</a>
        </div>
    @elseif($state === 'receipt')
        {{-- ---------- Keadaan: struk pembayaran ---------- --}}
        <div class="struk-toolbar">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                @include('partials.icon', ['name' => 'download', 'size' => 16]) Cetak / Simpan PDF
            </button>
        </div>

        <article class="struk">
            <div class="struk-head">
                <img class="struk-logo" src="{{ asset('images/logo-mark.svg') }}" alt="" width="42" height="42">
                <span>
                    <span class="struk-brand">GantinoID Store</span><br>
                    <span class="struk-brand-sub">Struk Pembayaran &middot; GantinoID Pay</span>
                </span>
            </div>

            <div class="struk-status {{ $paid ? 'is-paid' : 'is-pending' }}">
                <span class="struk-status-icon">@include('partials.icon', ['name' => $paid ? 'check' : 'clock', 'size' => 22])</span>
                <span class="struk-status-text">
                    <strong>{{ $paid ? 'Pembayaran Berhasil' : 'Pembayaran Diterima' }}</strong>
                    <span>{{ $paid ? 'Pesanan selesai diproses' : 'Menunggu verifikasi admin' }}</span>
                </span>
            </div>

            <div class="struk-sep"></div>

            <dl class="struk-rows">
                <div><dt>Kode Pesanan</dt><dd class="mono">{{ $doc['code'] }}</dd></div>
                <div><dt>Metode</dt><dd>{{ $doc['method'] }}</dd></div>
                @if(! empty($doc['reference']))
                    <div><dt>No. Referensi</dt><dd class="mono">{{ $doc['reference'] }}</dd></div>
                @endif
                <div><dt>Pembeli</dt><dd>{{ $doc['buyer']['name'] }}</dd></div>
                <div><dt>Waktu</dt><dd>{{ $stampAt->format('d-m-Y H:i') }} WIB</dd></div>
                <div><dt>Status</dt><dd>{{ $doc['label'] }}</dd></div>
            </dl>

            <div class="struk-sep"></div>

            <div class="struk-items">
                @foreach($doc['items'] as $item)
                    <div class="struk-item">
                        <span>
                            {{ $item['name'] }}
                            @if(! empty($item['sub']))<small>{{ $item['sub'] }}</small>@endif
                            @if(($item['qty'] ?? 1) > 1)<small>Jumlah: {{ $item['qty'] }}</small>@endif
                        </span>
                        <span class="mono">Rp {{ number_format($item['price'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @if(($doc['discount'] ?? 0) > 0)
                    <div class="struk-item">
                        <span>
                            Potongan Promo
                            <small class="mono">{{ $doc['promo_code'] }}</small>
                        </span>
                        <span class="mono discount-amount">− Rp {{ number_format($doc['discount'], 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            <div class="struk-sep"></div>

            <div class="struk-total">
                <span>Total</span>
                <strong>{{ $amount }}</strong>
            </div>

            <p class="struk-note">
                @if($paid)
                    Pembayaranmu sudah terverifikasi dan pesanan selesai diproses. Simpan struk ini sebagai bukti.
                @else
                    Simpan (screenshot) struk ini sebagai bukti pembayaran, lalu unggah di halaman pesanan
                    agar admin dapat memverifikasi.
                @endif
            </p>
            <p class="struk-sim">
                Dokumen ini dihasilkan oleh portal pembayaran <strong>simulasi</strong> GantinoID —
                tidak ada uang yang benar-benar berpindah.
            </p>
        </article>

        <div class="struk-cta">
            @if($paid)
                <a href="{{ $backUrl }}" class="btn btn-primary btn-lg btn-block">Lihat Pesanan</a>
            @else
                <a href="{{ $backUrl }}" class="btn btn-primary btn-lg btn-block">
                    @include('partials.icon', ['name' => 'download', 'size' => 18]) Unggah Struk sebagai Bukti
                </a>
            @endif
            <a href="{{ route('invoice.show', $doc['code']) }}" class="btn btn-ghost btn-block mt-8">Lihat Invoice</a>
        </div>
    @else
        {{-- ---------- Keadaan: instruksi pembayaran ---------- --}}
        <div class="portal-card">
            <div class="portal-channel-head">
                <span class="portal-channel-badge">{{ $channel['label'] }}</span>
                <h2>{{ $channel['headline'] }}</h2>
                <p class="muted">{{ $channel['note'] }}</p>
            </div>

            @if($channel['type'] === 'qr')
                <div class="portal-qr">
                    @include('partials.fake-qr', ['code' => $doc['code']])
                </div>
            @else
                <div class="portal-account">
                    <span class="portal-account-label">{{ $channel['account_label'] }}</span>
                    <div class="portal-account-value">
                        <span class="mono">{{ $channel['account'] }}</span>
                        <button type="button" class="portal-copy" data-copy="{{ $channel['account'] }}">Salin</button>
                    </div>
                </div>
            @endif

            <ol class="portal-steps">
                @foreach($channel['steps'] as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>

            <form action="{{ route('payment.confirm', $doc['code']) }}" method="POST" class="portal-actions">
                @csrf
                <button type="submit" class="btn btn-success btn-lg btn-block">
                    @include('partials.icon', ['name' => 'check', 'size' => 18]) Saya Sudah Bayar
                </button>
            </form>

            <p class="portal-hint">
                Setelah menekan tombol di atas, <strong>struk pembayaran</strong> akan muncul di halaman ini
                dan bisa kamu unggah sebagai bukti.
            </p>
        </div>

        <p class="portal-note">
            Belum yakin? <a href="{{ $backUrl }}">Kembali ke halaman pesanan</a>.
        </p>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Tombol "Salin" untuk nomor Virtual Account / nomor e-wallet.
    document.querySelectorAll('.portal-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            var value = button.getAttribute('data-copy') || '';
            var done  = function () {
                var original = button.textContent;
                button.textContent = 'Tersalin';
                setTimeout(function () { button.textContent = original; }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(done, done);
            } else {
                var field = document.createElement('textarea');
                field.value = value;
                document.body.appendChild(field);
                field.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(field);
                done();
            }
        });
    });
</script>
@endpush
