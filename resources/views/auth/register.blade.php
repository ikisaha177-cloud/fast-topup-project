@extends('layouts.app')

@section('title', 'Daftar - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="auth-shell">
            <aside class="auth-aside">
                <span class="hero-badge"><span class="hero-dot"></span> Cepat &amp; aman</span>
                <h2>Bikin akun, <br><span class="text-gradient">mulai top up</span></h2
                <p>Cukup 10 detik untuk mulai top up game dan belanja item favoritmu.</p>

                <ul class="auth-list">
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'gamepad', 'size' => 16])</span> Akses semua game populer &amp; nominal lengkap.</li>
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'cart', 'size' => 16])</span> Belanja voucher, skin, dan akun di marketplace.</li>
                    <li><span class="al-icon">@include('partials.icon', ['name' => 'bell', 'size' => 16])</span> Notifikasi status transaksi langsung ke emailmu.</li>
                </ul>
            </aside>

            <div class="auth-panel">
                <div class="card auth-card">
                    <h1 class="auth-title">Buat Akun Baru</h1>
                    <p class="muted">Buat akun untuk mulai top up dan belanja.</p>

                    <form action="{{ route('register') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control"
                                   value="{{ old('name') }}" placeholder="Nama kamu" required autofocus>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                   value="{{ old('email') }}" placeholder="nama@email.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">Daftar Sekarang</button>
                    </form>

                    <p class="muted text-center mt-16">
                        Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

