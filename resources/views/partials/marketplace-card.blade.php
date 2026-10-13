{{-- Partial: kartu produk marketplace (dipakai di home, katalog, dan rekomendasi).
     Pemakaian:
       @include('partials.marketplace-card', ['product' => $product, 'index' => $index])
     Opsi:
       'showSeller'    => true|false  (tampilkan nama penjual, default true)
       'buttonVariant' => 'primary'|'outline' (default 'primary')
--}}
@php
    $index         = $index ?? 0;
    $showSeller    = $showSeller ?? true;
    $buttonVariant = $buttonVariant ?? 'primary';
    $sold          = (int) $product->sold_count;
@endphp

<div class="card card-hover product-card reveal d{{ ($index % 4) + 1 }}">
    @include('partials.marketplace-image', ['product' => $product])

    <div class="product-subrow">
        <span>{{ $product->category ?? 'Umum' }}</span>
        <span class="sold-line">
            @if($sold > 0)
                Terjual {{ number_format($sold) }}
            @else
                Belum terjual
            @endif
        </span>
    </div>

    <div class="product-name">{{ $product->name }}</div>

    @include('partials.marketplace-price', ['product' => $product])

    <div class="product-meta">
        <span class="seller-line">Stok: {{ $product->stock }}</span>
        @if($showSeller)
            <span class="muted">{{ $product->seller->name ?? 'Penjual' }}</span>
        @endif
    </div>

    <a href="{{ route('marketplace.show', $product->slug) }}"
       class="btn btn-{{ $buttonVariant }} btn-block mt-16">Lihat Detail</a>
</div>
