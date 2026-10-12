@extends('layouts.app')

@section('title', 'Halaman Kedaluwarsa - TopUpMarket')

@section('content')
<section class="section">
    <div class="container">
        <div class="card empty" style="padding:72px 20px;">
            <div class="error-code">419</div>
            <h1 class="section-title">Sesi Kedaluwarsa</h1>
            <p class="section-subtitle">Halaman sudah kedaluwarsa karena terlalu lama. Silakan muat ulang lalu coba lagi.</p>
            <div class="mt-16">
                <a href="{{ url()->previous() }}" class="btn btn-primary">Muat Ulang Halaman</a>
            </div>
        </div>
    </div>
</section>
@endsection
