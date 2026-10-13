@extends('layouts.app')

@section('title', 'Checkout Marketplace - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="steps">
            <div class="step done">Keranjang</div>
            <div class="step current">Pembayaran</div>
            <div class="step">Selesai</div>
        </div>

        <h1 class="section-title">Checkout Marketplace</h1>
        <p class="section-subtitle">Periksa pesanan lalu pilih metode pembayaran.</p>

        <div class="grid grid-2 grid-start">
            <div class="card">
                <h3>Ringkasan Pesanan</h3>
                @foreach($cart as $item)
                    <div class="summary-row">
                        <div class="item-cell">
                            @include('partials.marketplace-image', [
                                'product' => (object) ['image' => $item['image'] ?? null, 'name' => $item['name']],
                                'variant' => 'is-thumb',
                            ])
                            <span>{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                        </div>
                        <span>Rp {{ number_format($item['price'] * $item['quantity'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="summary-total">
                    <span>Total</span>
                    <span class="amount">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="card summary-card">
                <h3>Metode Pembayaran</h3>
                <form action="{{ route('marketplace.checkout.process') }}" method="POST">
                    @csrf
                    <div class="payment-list">
                        @foreach($paymentMethods as $method => $desc)
                            <label class="payment-item">
                                <input type="radio" name="payment_method" value="{{ $method }}"
                                       {{ old('payment_method') === $method ? 'checked' : '' }} required>
                                <span class="payment-box">
                                    {{ $method }}
                                    <small>{{ $desc }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @error('cart') <div class="invalid-feedback">{{ $message }}</div> @enderror

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-24">Bayar Sekarang</button>
                    <a href="{{ route('cart.index') }}" class="btn btn-ghost btn-block mt-16">Kembali ke Keranjang</a>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
