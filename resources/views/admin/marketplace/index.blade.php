@extends('layouts.app')

@section('title', 'Kelola Marketplace - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Kelola Marketplace</h1>
                <p class="section-subtitle mb-0">Produk dari seluruh penjual.</p>
            </div>
        </div>

        @include('partials.admin-nav')

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.marketplace.index') }}" class="searchbar">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama produk atau kategori…">
            </form>
        </div>

        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Penjual</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->seller->name ?? '-' }}</td>
                            <td>{{ $product->category ?? '-' }}</td>
                            <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td>
                                <strong>{{ $product->stock }}</strong>
                                @if($product->stock < 10)
                                    <span class="badge badge-pending">Menipis</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-muted">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.marketplace.edit', $product->id) }}" class="btn btn-outline btn-sm">Edit</a>
                                    <form action="{{ route('admin.marketplace.toggle', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('admin.marketplace.destroy', $product->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus produk marketplace ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center muted">Tidak ada produk yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
</section>
@endsection
