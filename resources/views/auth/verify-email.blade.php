@extends('layouts.app')

@section('title', 'Verifikasi Email - FastTopup Store')
@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<section class="section">
    <div class="container" style="max-width:640px;">
        <div class="card" style="padding:36px 28px;">
            <span class="eyebrow">Satu Langkah Lagi</span>
            <h1 class="section-title" style="font-size:28px;">Verifikasi Email Kamu</h1>
            <p class="section-subtitle">
                Kami sudah mengirim tautan verifikasi ke
                <strong>{{ auth()->user()->email }}</strong>.
                Buka tautan tersebut untuk mengaktifkan notifikasi pesanan dan pengingat pembayaran.
            </p>

            <p class="muted" style="font-size:14px;">
                Tidak menerima emailnya? Cek folder spam, atau minta tautan baru di bawah ini.
            </p>

            <form method="POST" action="{{ route('verification.resend') }}" class="mt-16">
                @csrf
                <button type="submit" class="btn btn-primary btn-block">Kirim Ulang Tautan Verifikasi</button>
            </form>
        </div>
    </div>
</section>
@endsection

