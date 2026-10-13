@extends('layouts.app')

@section('title', 'TopUpMarket — Top Up Game Instan & Marketplace Item Game')

@section('content')

@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $totalNominal = $games->sum('products_count');
    $featured = $games->take(8);
@endphp

{{-- ============ HERO ============ --}}
<section class="hero">
    <div class="aurora">
        <span class="aurora-blob b1"></span>
        <span class="aurora-blob b2"></span>
        <span class="aurora-blob b3"></span>
    </div>

    <div class="container hero-grid">
        <div class="hero-inner">
            <span class="hero-badge"><span class="hero-dot"></span> Pembayaran aman, online 24/7</span>
            <h1>Top Up Game &amp; Belanja Item<br><span class="text-gradient">Tanpa Ribet, Tanpa Nunggu</span></h1>
            <p>
                Pilih game favoritmu, masukkan User ID, lalu tentukan nominal. Bayar pakai QRIS, e-wallet,
                atau transfer bank — pesanan diproses setelah pembayaran terverifikasi dan tercatat rapi di akunmu.
            </p>

            <div class="hero-cta">
                @if($isAdmin)
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-lg">Buka Dashboard Admin</a>
                @else
                    <a href="{{ route('topup.index') }}" class="btn btn-primary btn-lg">Top Up Sekarang →</a>
                    <a href="{{ route('marketplace.index') }}" class="btn btn-outline btn-lg">Jelajahi Marketplace</a>
                @endif
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <strong>{{ $games->count() }}</strong>
                    <span>Game tersedia</span>
                </div>
                <div class="hero-stat">
                    <strong>{{ $totalNominal }}</strong>
                    <span>Pilihan nominal</span>
                </div>
                <div class="hero-stat">
                    <strong>{{ $totalMarketplaceProducts }}</strong>
                    <span>Item marketplace</span>
                </div>
                <div class="hero-stat">
                    <strong>{{ $paymentMethodCount }}</strong>
                    <span>Metode bayar</span>
                </div>
            </div>
        </div>

        {{-- Visual: kartu melayang --}}
        <div class="hero-visual">
            <span class="hero-orbit"></span>

            <div class="float-mini m1">
                <span class="mini-icon">@include('partials.icon', ['name' => 'bolt', 'size' => 16])</span>
                <span>Saldo Masuk<small>Terverifikasi</small></span>
            </div>

            <div class="float-card">
                <div class="float-card-head">
                    <strong>Pesanan Kamu</strong>
                    <span class="chip-ok">● Selesai</span>
                </div>
                @if($heroProduct)
                    <div class="float-row"><span>Game</span><span>{{ $heroProduct->gameTitle->name ?? 'Game' }}</span></div>
                    <div class="float-row"><span>Nominal</span><span>{{ $heroProduct->name }}</span></div>
                    <div class="float-row"><span>Metode</span><span>QRIS</span></div>
                    <div class="float-row"><span>Total</span><span class="float-total">Rp {{ number_format($heroProduct->price, 0, ',', '.') }}</span></div>
                @else
                    <div class="float-row"><span>Game</span><span>Mobile Legends</span></div>
                    <div class="float-row"><span>Nominal</span><span>500 Diamonds</span></div>
                    <div class="float-row"><span>Metode</span><span>QRIS</span></div>
                    <div class="float-row"><span>Total</span><span class="float-total">Rp 145.000</span></div>
                @endif
            </div>

            <div class="float-mini m2">
                <span class="mini-icon">@include('partials.icon', ['name' => 'shield', 'size' => 16])</span>
                <span>100% Aman<small>Transaksi terlacak</small></span>
            </div>
        </div>
    </div>
</section>

{{-- ============ TICKER ============ --}}
<div class="ticker" aria-hidden="true">
    <div class="ticker-track">
        @for($i = 0; $i < 2; $i++)
            <span class="ticker-item"><span class="tick-dot"></span> Pembayaran QRIS &amp; E-Wallet</span>
            <span class="ticker-item"><span class="tick-dot"></span> Konfirmasi Pembayaran 24/7</span>
            <span class="ticker-item"><span class="tick-dot"></span> Harga Resmi &amp; Transparan</span>
            <span class="ticker-item"><span class="tick-dot"></span> Riwayat Pesanan Lengkap</span>
            <span class="ticker-item"><span class="tick-dot"></span> Penjual dengan Stok Real-time</span>
            <span class="ticker-item"><span class="tick-dot"></span> Tanpa Biaya Layanan Tersembunyi</span>
        @endfor
    </div>
</div>

{{-- ============ GAME POPULER ============ --}}
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="eyebrow">Katalog Game</span>
                <h2 class="section-title">Top Up Game Favoritmu</h2>
                <p class="section-subtitle mb-0">Semua game populer dengan harga resmi dan proses terverifikasi.</p>
            </div>
            @if(! $isAdmin)
                <a href="{{ route('topup.index') }}" class="btn btn-ghost">Lihat Semua Game →</a>
            @endif
        </div>

        <div class="grid grid-4">
            @forelse($featured as $index => $game)
                <div class="card card-hover game-card reveal d{{ ($index % 4) + 1 }}">
                    @if($index === 0)
                        <span class="game-badge is-hot">Terpopuler</span>
                    @elseif($game->products_count >= 4)
                        <span class="game-badge">Populer</span>
                    @endif

                    @include('partials.game-icon', ['game' => $game])
                    <h3 class="card-title">{{ $game->name }}</h3>
                    <p class="muted">{{ $game->category ?? 'Game' }} &middot; {{ $game->products_count }} nominal</p>

                    @if($isAdmin)
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-block mt-16">Kelola Produk</a>
                    @else
                        <a href="{{ route('topup.show', $game->slug) }}" class="btn btn-primary btn-block mt-16">Top Up</a>
                    @endif
                </div>
            @empty
                <div class="empty">Belum ada game tersedia.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- ============ PRODUK TERBARU ============ --}}
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <span class="eyebrow">Marketplace</span>
                <h2 class="section-title">Item Game Terbaru</h2>
                <p class="section-subtitle mb-0">Voucher, skin, akun, dan koin dari penjual yang terdaftar.</p>
            </div>
            <a href="{{ route('marketplace.index') }}" class="btn btn-ghost">Lihat Semua Item →</a>
        </div>

        <div class="grid grid-4">
            @forelse($marketplaceProducts as $index => $product)
                <div class="card card-hover product-card reveal d{{ ($index % 4) + 1 }}">
                    @include('partials.marketplace-image', ['product' => $product])
                    <div class="product-subrow">
                        <span>{{ $product->category ?? 'Umum' }}</span>
                        <span class="sold-line">Terjual {{ number_format($product->sold_count) }}</span>
                    </div>
                    <div class="product-name">{{ $product->name }}</div>
                    @include('partials.marketplace-price', ['product' => $product])
                    <div class="product-meta">
                        <span class="seller-line">Stok: {{ $product->stock }}</span>
                        <span class="muted">{{ $product->seller->name ?? 'Penjual' }}</span>
                    </div>
                    <a href="{{ route('marketplace.show', $product->slug) }}" class="btn btn-primary btn-block mt-16">Lihat Detail</a>
                </div>
            @empty
                <div class="empty">Belum ada produk marketplace.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- ============ STAT BAND ============ --}}
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="stat-band reveal">
            <div class="band-item">
                <strong>{{ $games->count() }}</strong>
                <span>Game Terdaftar</span>
            </div>
            <div class="band-item">
                <strong>{{ $totalNominal }}</strong>
                <span>Pilihan Nominal</span>
            </div>
            <div class="band-item">
                <strong>24/7</strong>
                <span>Layanan Nonstop</span>
            </div>
            <div class="band-item">
                <strong>{{ $paymentMethodCount }}</strong>
                <span>Metode Pembayaran</span>
            </div>
        </div>
    </div>
</section>

{{-- ============ KENAPA KAMI ============ --}}
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="text-center reveal" style="margin-bottom:30px;">
            <span class="eyebrow" style="justify-content:center;">Kenapa TopUpMarket</span>
            <h2 class="section-title">Dibuat Biar Top Up Gak Bikin Pusing</h2>
            <p class="section-subtitle mb-0">Cepat, aman, dan transparan — dari pembayaran sampai pesanan selesai.</p>
        </div>

        <div class="trust-strip">
            <div class="trust-item reveal d1">
                <span class="trust-icon">@include('partials.icon', ['name' => 'bolt', 'size' => 20])</span>
                <div>
                    <strong>Proses Terverifikasi</strong>
                    <span>Setelah pembayaran terverifikasi, pesanan langsung diproses tanpa antre lama.</span>
                </div>
            </div>
            <div class="trust-item reveal d2">
                <span class="trust-icon">@include('partials.icon', ['name' => 'shield', 'size' => 20])</span>
                <div>
                    <strong>Transaksi Terlacak</strong>
                    <span>Setiap pesanan punya kode unik dan riwayat lengkap di akunmu.</span>
                </div>
            </div>
            <div class="trust-item reveal d3">
                <span class="trust-icon">@include('partials.icon', ['name' => 'card', 'size' => 20])</span>
                <div>
                    <strong>Pembayaran Fleksibel</strong>
                    <span>QRIS, DANA, GoPay, OVO, dan transfer bank semua bisa dipakai.</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============ CARA KERJA ============ --}}
<section class="section" id="cara-kerja" style="padding-top:0;">
    <div class="container">
        <div class="text-center reveal" style="margin-bottom:30px;">
            <span class="eyebrow" style="justify-content:center;">Cara Kerja</span>
            <h2 class="section-title">Tiga Langkah, Selesai</h2>
            <p class="section-subtitle mb-0">Gak perlu daftar panjang. Pilih, bayar, beres.</p>
        </div>

        <div class="step-cards">
            <div class="step-card reveal d1">
                <span class="step-no">1</span>
                <h3 class="mb-0">Isi Data Akun Game</h3>
                <p class="muted mb-0">Masukkan User ID dan Server ID (jika ada) dari akun game kamu.</p>
            </div>
            <div class="step-card reveal d2">
                <span class="step-no">2</span>
                <h3 class="mb-0">Pilih Nominal &amp; Bayar</h3>
                <p class="muted mb-0">Tentukan nominal, lalu pilih metode pembayaran yang paling nyaman.</p>
            </div>
            <div class="step-card reveal d3">
                <span class="step-no">3</span>
                <h3 class="mb-0">Selesai &amp; Tercatat</h3>
                <p class="muted mb-0">Pembayaran terverifikasi dan pesanan tersimpan di riwayat transaksimu.</p>
            </div>
        </div>
    </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="section" id="faq" style="padding-top:0;">
    <div class="container">
        <div class="text-center reveal" style="margin-bottom:30px;">
            <span class="eyebrow" style="justify-content:center;">FAQ</span>
            <h2 class="section-title">Pertanyaan yang Sering Ditanya</h2>
            <p class="section-subtitle mb-0">Belum ketemu jawabannya? Hubungi kami lewat halaman profil.</p>
        </div>

        <div class="faq">
            <details class="faq-item reveal d1" open>
                <summary>Berapa lama pesanan saya diproses?</summary>
                <div class="faq-body">Setelah pembayaran terverifikasi admin, pesanan langsung diproses. Statusnya bisa kamu pantau di halaman Riwayat Transaksi.</div>
            </details>
            <details class="faq-item reveal d2">
                <summary>Metode pembayaran apa saja yang tersedia?</summary>
                <div class="faq-body">Kami mendukung QRIS, DANA, GoPay, OVO, dan transfer bank. Pilih yang paling nyaman saat checkout.</div>
            </details>
            <details class="faq-item reveal d3">
                <summary>Apakah data akun game saya aman?</summary>
                <div class="faq-body">Aman. Kami hanya membutuhkan User ID (dan Server ID bila diperlukan) untuk mengirim item. Kami tidak pernah meminta password akunmu.</div>
            </details>
            <details class="faq-item reveal d4">
                <summary>Bagaimana kalau saya salah memasukkan User ID?</summary>
                <div class="faq-body">Selama status pesanan masih <em>pending</em>, kamu bisa membatalkannya dan stock otomatis dikembalikan. Setelah berhasil, pesanan tidak bisa dibatalkan.</div>
            </details>
        </div>
    </div>
</section>

{{-- ============ CTA ============ --}}
@if(! $isAdmin)
<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="cta-band reveal">
            <span class="eyebrow" style="justify-content:center;">Siap Mulai?</span>
            <h2>Siap Top Up Sekarang?</h2>
            <p>Pilih game favoritmu dan selesaikan hanya dalam satu menit.</p>
            <div class="hero-cta">
                <a href="{{ route('topup.index') }}" class="btn btn-primary btn-lg">Mulai Top Up</a>
                <a href="{{ route('marketplace.index') }}" class="btn btn-outline btn-lg">Cek Marketplace</a>
            </div>
            @guest
                <p class="muted mt-16">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a> dalam 10 detik.</p>
            @endguest
        </div>
    </div>
</section>
@endif

@endsection
