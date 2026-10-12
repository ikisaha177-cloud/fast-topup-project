@extends('layouts.app')

@section('title', 'Kelola Produk Top Up - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Kelola Produk Top Up</h1>
                <p class="section-subtitle mb-0">Nominal dan harga tiap game.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Tambah Produk</a>
        </div>

        @include('partials.admin-nav')

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.products.index') }}" class="searchbar">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama produk…">
            </form>
            <div class="chips">
                <a href="{{ route('admin.products.index', $search ? ['q' => $search] : []) }}"
                   class="chip {{ $gameId ? '' : 'active' }}">Semua Game</a>
                @foreach($games as $game)
                    <a href="{{ route('admin.products.index', array_filter(['q' => $search, 'game' => $game->id])) }}"
                       class="chip {{ (string) $gameId === (string) $game->id ? 'active' : '' }}">{{ $game->name }}</a>
                @endforeach
            </div>
        </div>

        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->gameTitle->name ?? '-' }}</td>
                            <td>{{ $product->name }}</td>
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
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-outline btn-sm">Edit</a>
                                    <form action="{{ route('admin.products.toggle', $product->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus produk ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center muted">Tidak ada produk yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
</section>
@endsection
