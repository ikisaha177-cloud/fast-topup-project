@extends('layouts.app')

@section('title', 'Edit Produk Top Up - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Edit Produk Top Up</h1>
                <p class="section-subtitle mb-0">Perbarui detail nominal.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:560px;">
            <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.products._form', ['product' => $product])
                <button type="submit" class="btn btn-primary btn-block mt-16">Update</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
