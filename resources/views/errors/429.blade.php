@extends('layouts.app')

@section('title', 'Terlalu Banyak Permintaan - FastTopup Store')
@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
<section class="section">
    <div class="container">
        <div class="card empty" style="padding:72px 20px;">
            <div class="error-code">429</div>
            <h1 class="section-title">Terlalu Banyak Permintaan</h1>
            <p class="section-subtitle">Kamu mengirim permintaan terlalu sering. Tunggu sebentar, lalu coba lagi.</p>
            <div class="mt-16">
                <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Home</a>
            </div>
        </div>
    </div>
</section>
@endsection

