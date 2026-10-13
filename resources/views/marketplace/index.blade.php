@extends('layouts.app')

@section('title', 'Marketplace - TopUpMarket')

@section('content')
<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Marketplace</span>
        <h1>Marketplace Item Game</h1>
        <p>Voucher, skin, akun, dan koin dari penjual yang terdaftar. Semua transaksi tercatat aman.</p>

        <div class="banner-stats">
            <div class="banner-stat">
                <strong>{{ $totalProducts }}</strong>
                <span>Produk tersedia</span>
            </div>
            <div class="banner-stat">
                <strong>{{ $categories->count() }}</strong>
                <span>Kategori</span>
            </div>
            <div class="banner-stat">
                <strong>{{ $sellerCount }}</strong>
                <span>Penjual aktif</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="toolbar">
            <form method="GET" action="{{ route('marketplace.index') }}" class="searchbar">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari produk, skin, voucher…">
            </form>
            <div class="chips">
                <a href="{{ route('marketplace.index', $search ? ['q' => $search] : []) }}"
                   class="chip {{ $category ? '' : 'active' }}">Semua</a>
                @foreach($categories as $cat)
                    <a href="{{ route('marketplace.index', array_filter(['q' => $search, 'kategori' => $cat])) }}"
                       class="chip {{ $category === $cat ? 'active' : '' }}">{{ $cat }}</a>
                @endforeach
            </div>
        </div>

        @if($products->isEmpty())
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'search', 'size' => 34])</span>
                Tidak ada produk yang cocok.
                @if($search || $category)
                    <div class="mt-16">
                        <a href="{{ route('marketplace.index') }}" class="btn btn-outline">Hapus Filter</a>
                    </div>
                @endif
            </div>
        @else
            <div class="grid grid-4">
                @foreach($products as $index => $product)
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
                            <span class="muted">{{ $product->seller->name ?? '-' }}</span>
                        </div>
                        <a href="{{ route('marketplace.show', $product->slug) }}" class="btn btn-primary btn-block mt-16">Lihat Detail</a>
                    </div>
                @endforeach
            </div>

            {{ $products->links() }}
        @endif
    </div>
</section>
@endsection
