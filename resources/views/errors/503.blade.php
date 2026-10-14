@extends('layouts.app')

@section('title', 'Sedang Pemeliharaan - FastTopup Store')
@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="card empty" style="padding:72px 20px;">
            <div class="error-code">503</div>
            <h1 class="section-title">Sedang Pemeliharaan</h1>
            <p class="section-subtitle">Kami sedang memperbarui layanan. Coba lagi beberapa saat lagi, ya.</p>
            <div class="mt-16">
                <a href="{{ route('home') }}" class="btn btn-primary">Muat Ulang</a>
            </div>
        </div>
    </div>
</section>
@endsection

