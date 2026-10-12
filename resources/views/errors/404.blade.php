@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="card empty" style="padding:72px 20px;">
            <div class="error-code">404</div>
            <h1 class="section-title">Halaman Tidak Ditemukan</h1>
            <p class="section-subtitle">Halaman yang kamu cari tidak ada atau sudah dipindahkan.</p>
            <div class="mt-16">
                <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Home</a>
            </div>
        </div>
    </div>
</section>
@endsection
