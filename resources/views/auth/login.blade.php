@extends('layouts.app')

@section('title', 'Masuk - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="auth-shell">
            <aside class="auth-aside">
                <span class="hero-badge"><span class="hero-dot"></span> Selamat datang kembali</span>
                <h2>Masuk &amp; lanjutkan<br><span class="text-gradient">petualanganmu</span></h2>
                <p>Kelola transaksi, pantau pesanan, dan top up game favoritmu dari satu tempat.</p>

                <ul class="auth-list">
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'bolt', 'size' => 16])</span> Pembayaran QRIS &amp; e-wallet, diproses setelah terverifikasi.</li>
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'shield', 'size' => 16])</span> Riwayat transaksi &amp; pesanan tercatat rapi.</li>
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'card', 'size' => 16])</span> Bayar pakai QRIS, e-wallet, atau transfer bank.</li>
                </ul>
            </aside>

            <div class="auth-panel">
                <div class="card auth-card">
                    <h1 class="auth-title">Masuk ke Akunmu</h1>
                    <p class="muted">Masuk untuk mengelola transaksi kamu.</p>

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex-between" style="margin-bottom:16px;">
                            <label class="checkbox" for="remember">
                                <input type="checkbox" name="remember" id="remember" value="1">
                                <span>Ingat saya</span>
                            </label>
                            <a href="{{ route('password.request') }}" style="font-size:13px;">Lupa password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">Masuk</button>
                    </form>

                    <p class="muted text-center mt-16">
                        Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

