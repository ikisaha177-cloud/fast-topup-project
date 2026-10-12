{{-- Partial: harga produk marketplace + harga coret & badge diskon bila ada.
     Pemakaian: @include('partials.marketplace-price', ['product' => $product, 'size' => 'lg']) --}}
@php $size = $size ?? 'md'; @endphp
<div class="product-price-row">
    <span class="product-price{{ $size === 'lg' ? ' is-lg' : '' }}">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
    @if($product->hasDiscount())
        <span class="price-was">Rp {{ number_format($product->compare_at_price, 0, ',', '.') }}</span>
        <span class="discount-badge">-{{ $product->discountPercent() }}%</span>
    @endif
</div>
