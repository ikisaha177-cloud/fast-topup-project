@extends('layouts.app')

@section('title', $product->name . ' - Marketplace')

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('marketplace.index') }}">Marketplace</a> / {{ $product->name }}
        </p>

        <div class="detail-grid">
            <div class="card">
                @include('partials.marketplace-image', ['product' => $product, 'variant' => 'is-detail'])
            </div>

            <div>
                <div class="card">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <span class="muted">{{ $product->category ?? 'Umum' }}</span>
                        <span class="rating"><span class="star">★</span> 4.9 <span class="muted" style="font-weight:600;">(terjual)</span></span>
                    </div>
                    <h1 style="font-size:26px; margin:8px 0;">{{ $product->name }}</h1>
                    <div class="product-price" style="font-size:30px;">Rp {{ number_format($product->price, 0, ',', '.') }}</div>

                    <div class="pill-row">
                        <span class="pill">Stok <strong>{{ $product->stock }}</strong></span>
                        <span class="pill">Penjual <strong>{{ $product->seller->name ?? '-' }}</strong></span>
                        @if($product->stock > 0)
                            <span class="pill">Status <strong style="color:var(--lime)">Tersedia</strong></span>
                        @else
                            <span class="pill">Status <strong style="color:var(--red)">Habis</strong></span>
                        @endif
                    </div>

                    <p class="muted mt-16">{{ $product->description ?: 'Tidak ada deskripsi.' }}</p>

                    <div class="mt-16" style="display:flex; flex-direction:column; gap:8px;">
                        <div class="summary-row" style="padding:8px 0;">
                            <span>Pembayaran</span><span>QRIS · E-Wallet · Transfer Bank</span>
                        </div>
                        <div class="summary-row" style="padding:8px 0; border-bottom:none;">
                            <span>Pengiriman item</span><span>Otomatis setelah pembayaran</span>
                        </div>
                    </div>

                    @auth
                        @if(auth()->user()->isAdmin())
                            <p class="muted mt-24">Akun admin tidak dapat melakukan pembelian. Kelola produk lewat menu Marketplace di dashboard admin.</p>
                        @elseif($product->stock > 0)
                            <form action="{{ route('marketplace.add-to-cart', $product->slug) }}" method="POST" class="mt-24">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label" for="quantity">Jumlah</label>
                                    <input type="number" id="quantity" name="quantity" class="form-control"
                                           value="{{ old('quantity', 1) }}" min="1" max="{{ $product->stock }}" required>
                                    @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-block btn-lg">Tambah ke Keranjang</button>
                            </form>
                        @else
                            <div class="alert alert-error mt-24">Stok habis. Coba produk serupa di bawah.</div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-block btn-lg mt-24">Login untuk Membeli</a>
                        <p class="muted mt-8 text-center">Belum punya akun? <a href="{{ route('register') }}">Daftar gratis</a>.</p>
                    @endauth
                </div>
            </div>
        </div>

        @if($related && $related->isNotEmpty())
            <div class="section-head mt-32" style="margin-top:40px;">
                <div>
                    <span class="eyebrow">Rekomendasi</span>
                    <h2 class="section-title" style="font-size:22px;">Produk Serupa</h2>
                    <p class="section-subtitle mb-0">Dari kategori yang sama.</p>
                </div>
            </div>

            <div class="grid grid-4">
                @foreach($related as $index => $item)
                    <div class="card card-hover product-card reveal d{{ ($index % 4) + 1 }}">
                        @include('partials.marketplace-image', ['product' => $item])
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:12px;">
                            <span class="muted">{{ $item->category ?? 'Umum' }}</span>
                            <span class="rating"><span class="star">★</span> 4.9</span>
                        </div>
                        <div class="product-name">{{ $item->name }}</div>
                        <div class="product-price">Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                        <div class="product-meta">
                            <span class="seller-line">Stok: {{ $item->stock }}</span>
                        </div>
                        <a href="{{ route('marketplace.show', $item->slug) }}" class="btn btn-outline btn-block mt-16">Lihat Detail</a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
