@extends('layouts.app')

@section('title', 'Keranjang - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="steps">
            <div class="step current">Keranjang</div>
            <div class="step">Pembayaran</div>
            <div class="step">Selesai</div>
        </div>

        <div class="page-head">
            <div>
                <h1 class="section-title">Keranjang</h1>
                <p class="section-subtitle mb-0">Periksa produk sebelum checkout.</p>
            </div>
            <a href="{{ route('marketplace.index') }}" class="btn btn-ghost">Lanjut Belanja</a>
        </div>

        @if(empty($cart))
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'cart', 'size' => 34])</span>
                Keranjang masih kosong.
                <div class="mt-16">
                    <a href="{{ route('marketplace.index') }}" class="btn btn-primary">Jelajahi Marketplace</a>
                </div>
            </div>
        @else
            <div class="detail-grid">
                <div class="card table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Produk</th>
                                <th scope="col">Harga</th>
                                <th scope="col">Jumlah</th>
                                <th scope="col">Subtotal</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart as $item)
                                <tr>
                                    <td class="wrap">
                                        <div class="item-cell">
                                            @include('partials.marketplace-image', [
                                                'product' => (object) ['image' => $item['image'] ?? null, 'name' => $item['name']],
                                                'variant' => 'is-thumb',
                                            ])
                                            <span>
                                                <a href="{{ route('marketplace.show', $item['slug']) }}" class="item-name">{{ $item['name'] }}</a>
                                                <span class="item-sub">Stok: {{ $item['stock'] }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td>Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('cart.update') }}" method="POST" class="qty-form">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                            <input type="number" name="quantity" class="form-control"
                                                   aria-label="Jumlah {{ $item['name'] }}"
                                                   value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock'] }}">
                                            <button type="submit" class="btn btn-outline btn-sm">Update</button>
                                        </form>
                                    </td>
                                    <td>Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</td>
                                    <td>
                                        <form action="{{ route('cart.remove') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $item['id'] }}">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card summary-card">
                    <h3>Ringkasan</h3>
                    <div class="summary-row">
                        <span>Jumlah item</span>
                        <span>{{ collect($cart)->sum('quantity') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya layanan</span>
                        <span>Gratis</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="amount">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>

                    <a href="{{ route('marketplace.checkout') }}" class="btn btn-primary btn-block btn-lg mt-16">Checkout</a>
                    <a href="{{ route('marketplace.index') }}" class="btn btn-ghost btn-block mt-16">Lanjut Belanja</a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

