@extends('layouts.app')

@section('title', 'Tambah Game - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Tambah Game</h1>
                <p class="section-subtitle mb-0">Game baru untuk top up.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:560px;">
            <form action="{{ route('admin.games.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('admin.games._form')
                <button type="submit" class="btn btn-primary btn-block mt-16">Simpan</button>
                <a href="{{ route('admin.games.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
