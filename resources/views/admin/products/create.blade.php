@extends('layouts.app')

@section('title', 'Tambah Produk Top Up - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Tambah Produk Top Up</h1>
                <p class="section-subtitle mb-0">Tambah nominal untuk game.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:560px;">
            <form action="{{ route('admin.products.store') }}" method="POST">
                @csrf
                @include('admin.products._form')
                <button type="submit" class="btn btn-primary btn-block mt-16">Simpan</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
