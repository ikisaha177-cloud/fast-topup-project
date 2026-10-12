@extends('layouts.app')

@section('title', 'Edit Produk Marketplace - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Edit Produk Marketplace</h1>
                <p class="section-subtitle mb-0">Perbarui produk dari penjual.</p>
            </div>
        </div>
        @include('partials.admin-nav')

        <div class="card" style="max-width:560px;">
            <form action="{{ route('admin.marketplace.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label">Penjual</label>
                    <input type="text" class="form-control" value="{{ $product->seller->name ?? '-' }}" disabled>
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Produk</label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="{{ old('name', $product->name) }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi</label>
                    <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Kategori</label>
                    <input type="text" id="category" name="category" class="form-control"
                           value="{{ old('category', $product->category) }}">
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label" for="price">Harga</label>
                        <input type="number" id="price" name="price" class="form-control"
                               value="{{ old('price', $product->price) }}" min="0" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="stock">Stok</label>
                        <input type="number" id="stock" name="stock" class="form-control"
                               value="{{ old('stock', $product->stock) }}" min="0" required>
                        @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="image">Gambar Produk</label>
                    @if(! empty($product->image))
                        @include('partials.marketplace-image', ['product' => $product])
                    @endif
                    <input type="file" id="image" name="image" class="form-control mt-8" accept="image/*">
                    <div class="form-hint">Format jpg, png, webp, atau svg. Maksimal 2 MB. Kosongkan bila tidak ingin mengubah gambar.</div>
                    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="is_active" name="is_active" value="1"
                        {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label for="is_active" style="cursor:pointer;">Aktif</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block mt-16">Update</button>
                <a href="{{ route('admin.marketplace.index') }}" class="btn btn-outline btn-block mt-16">Batal</a>
            </form>
        </div>
    </div>
</section>
@endsection
