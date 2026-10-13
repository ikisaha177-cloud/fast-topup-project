@extends('layouts.app')

@section('title', 'Tambah Kode Promo - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Tambah Kode Promo</h1>
                <p class="section-subtitle mb-0">Buat potongan harga baru untuk pembeli.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:640px;">
            <form action="{{ route('admin.promos.store') }}" method="POST">
                @csrf
                @include('admin.promos._form')
                <button type="submit" class="btn btn-primary btn-block mt-16">Simpan</button>
                <a href="{{ route('admin.promos.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
