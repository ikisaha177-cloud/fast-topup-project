@extends('layouts.app')

@section('title', $product->name . ' - Marketplace - GantinoID Store')

@php
    // Gambar produk untuk meta share: pakai berkas yang benar-benar ada.
    $shareImage = null;
    if ($product->image) {
        if (file_exists(public_path($product->image))) {
            $shareImage = asset($product->image);
        } elseif (file_exists(storage_path('app/public/' . $product->image))) {
            $shareImage = asset('storage/' . $product->image);
        }
    }
    $shareImage = $shareImage ?: asset('images/placeholder-product.svg');
@endphp

@section('og_type', 'product')
@section('og_title', $product->name . ' — GantinoID Store')
@section('og_description', \Illuminate\Support\Str::limit($product->description ?: 'Item game berkualitas dengan pembayaran aman di GantinoID Store.', 150))
@section('og_image', $shareImage)
@section('og_image_alt', $product->name . ' — GantinoID Store')

@section('meta')
@php
    // aggregateRating hanya dikirim bila benar-benar ada ulasan nyata —
    // data terstruktur tidak boleh memuat rating karangan.
    $ratingSummary = $product->ratingSummary();
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $product->description ?: 'Item game di GantinoID Store.',
        'image' => $shareImage,
        'category' => $product->category ?: 'Item Game',
        'offers' => [
            '@type' => 'Offer',
            'price' => (string) $product->price,
            'priceCurrency' => 'IDR',
            'availability' => $product->stock > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url' => url()->current(),
        ],
    ];

    if ($ratingSummary['count'] > 0) {
        $structuredData['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format($ratingSummary['average'], 1, '.', ''),
            'reviewCount' => $ratingSummary['count'],
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endsection

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
                    <div class="product-subrow" style="margin-top:0;">
                        <span>{{ $product->category ?? 'Umum' }}</span>
                        <span class="sold-line">
                            @if($product->sold_count > 0)
                                Terjual {{ number_format($product->sold_count) }}
                            @else
                                Belum terjual
                            @endif
                        </span>
                    </div>
                    <h1 style="font-size:26px; margin:8px 0;">{{ $product->name }}</h1>

                    {{-- Ringkasan rating: angka hanya muncul bila ada ulasan nyata. --}}
                    <div class="product-rating product-rating-lg">
                        @if($ratingSummary['count'] > 0)
                            @include('partials.rating-stars', ['rating' => $ratingSummary['average'], 'count' => $ratingSummary['count']])
                            <span class="product-rating-value">{{ number_format($ratingSummary['average'], 1, ',', '.') }}</span>
                            <a href="#ulasan" class="product-rating-link">
                                {{ number_format($ratingSummary['count']) }} ulasan
                            </a>
                        @else
                            <span class="muted">Belum ada ulasan</span>
                        @endif
                    </div>

                    @include('partials.marketplace-price', ['product' => $product, 'size' => 'lg'])
                    @if($product->hasDiscount())
                        <p class="muted mt-8" style="margin-bottom:0;">Hemat Rp {{ number_format($product->discountAmount(), 0, ',', '.') }} dari harga normal.</p>
                    @endif

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
                            <span>Pengiriman item</span><span>Setelah pembayaran diverifikasi</span>
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
                        <p class="muted mt-8 text-center">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>.</p>
                    @endauth
                </div>
            </div>
        </div>

        @include('partials.product-reviews', [
            'product'  => $product,
            'reviews'  => $reviews,
            'myOrder'  => $myOrder,
            'myReview' => $myReview,
        ])

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
                    @include('partials.marketplace-card', [
                        'product' => $item,
                        'index' => $index,
                        'showSeller' => false,
                        'buttonVariant' => 'outline',
                    ])
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
