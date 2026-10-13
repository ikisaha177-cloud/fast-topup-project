@extends('layouts.app')

@section('title', 'Checkout Top Up - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="steps">
            <div class="step done">Data &amp; Nominal</div>
            <div class="step current">Pembayaran</div>
            <div class="step">Selesai</div>
        </div>

        <h1 class="section-title">Checkout Top Up</h1>
        <p class="section-subtitle">Periksa pesananmu lalu pilih metode pembayaran.</p>

        <div class="grid grid-2 grid-start">
            <div class="card">
                <h3>Detail Pesanan</h3>
                <div class="item-cell" style="margin-bottom:14px;">
                    @include('partials.game-icon', ['game' => (object) ['icon' => $draft['game_icon'] ?? null, 'name' => $draft['game_name']], 'size' => 'xs'])
                    <span>
                        <span class="item-name">{{ $draft['game_name'] }}</span>
                        <span class="item-sub">{{ $draft['product_name'] }}</span>
                    </span>
                </div>
                <div class="summary-row"><span>User ID</span><span class="mono">{{ $draft['user_game_id'] }}</span></div>
                <div class="summary-row"><span>Server ID</span><span>{{ $draft['server_id'] ?: '-' }}</span></div>
                <div class="summary-total">
                    <span>Total Bayar</span>
                    <span class="amount">Rp {{ number_format($draft['amount'], 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="card summary-card">
                <h3>Metode Pembayaran</h3>
                <form action="{{ route('topup.pay') }}" method="POST">
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

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-24">Bayar Sekarang</button>
                    <a href="{{ route('topup.index') }}" class="btn btn-ghost btn-block mt-16">Batalkan</a>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
