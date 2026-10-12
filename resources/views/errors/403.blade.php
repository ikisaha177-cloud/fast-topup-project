@extends('layouts.app')

@section('title', 'Akses Ditolak - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="card empty" style="padding:72px 20px;">
            <div class="error-code">403</div>
            <h1 class="section-title">Akses Ditolak</h1>
            <p class="section-subtitle">Kamu tidak punya izin untuk membuka halaman ini.</p>
            <div class="mt-16">
                <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Home</a>
            </div>
        </div>
    </div>
</section>
@endsection
