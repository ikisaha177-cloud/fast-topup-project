{{-- Partial: tampilkan gambar produk marketplace.
     Mendukung aset statis di public/ maupun hasil upload di storage/app/public.
     Null-safe: produk yang sudah dihapus tetap menampilkan placeholder.
     Pemakaian: @include('partials.marketplace-image', ['product' => $product, 'variant' => 'is-thumb']) --}}
@php
    $image   = $product->image ?? null;
    $label   = $product->name ?? ($name ?? 'Produk');
    $variant = $variant ?? '';
    $publicExists  = $image && file_exists(public_path($image));
    $storageExists = $image && file_exists(storage_path('app/public/' . $image));

    if ($publicExists) {
        $imageUrl = asset($image);
    } elseif ($storageExists) {
        $imageUrl = asset('storage/' . $image);
    } else {
        $imageUrl = asset('images/products/placeholder-product.svg');
    }
@endphp

<div class="product-thumb{{ $variant ? ' ' . $variant : '' }}">
    <img src="{{ $imageUrl }}" alt="Gambar {{ $label }}"
         width="480" height="300" loading="lazy" decoding="async">
</div>
