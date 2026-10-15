@extends('layouts.app')

@section('title', 'Lupa Password - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="card auth-card">
            <span class="eyebrow">Pemulihan Akun</span>
            <h1 class="auth-title">Lupa Password</h1>
            <p class="muted">Masukkan email akun kamu. Kami akan mengirim link untuk membuat password baru.</p>

            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Kirim Link Reset</button>
            </form>

            <p class="muted text-center mt-16">
                Ingat password kamu? <a href="{{ route('login') }}">Kembali ke login</a>
            </p>
        </div>
    </div>
</section>
@endsection

