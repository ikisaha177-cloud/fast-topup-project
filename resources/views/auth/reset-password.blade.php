@extends('layouts.app')

@section('title', 'Reset Password - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <div class="card auth-card">
            <span class="eyebrow">Pemulihan Akun</span>
            <h1 class="auth-title">Reset Password</h1>
            <p class="muted">Buat password baru untuk akun kamu.</p>

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="{{ old('email', $email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password Baru</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required>
                    <div class="form-hint">Minimal 8 karakter.</div>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Simpan Password Baru</button>
            </form>
        </div>
    </div>
</section>
@endsection

