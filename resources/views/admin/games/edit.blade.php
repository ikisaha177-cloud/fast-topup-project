@extends('layouts.app')

@section('title', 'Edit Game - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Edit Game</h1>
                <p class="section-subtitle mb-0">Perbarui detail game.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:560px;">
            <form action="{{ route('admin.games.update', $game->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('admin.games._form', ['game' => $game])
                <button type="submit" class="btn btn-primary btn-block mt-16">Update</button>
                <a href="{{ route('admin.games.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
