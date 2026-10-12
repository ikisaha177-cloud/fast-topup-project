@extends('layouts.app')

@section('title', 'Kelola Game - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Kelola Game</h1>
                <p class="section-subtitle mb-0">Daftar game yang tersedia untuk top up.</p>
            </div>
            <a href="{{ route('admin.games.create') }}" class="btn btn-primary">+ Tambah Game</a>
        </div>

        @include('partials.admin-nav')

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.games.index') }}" class="searchbar">
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, slug, atau kategori…">
            </form>
        </div>

        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Kategori</th>
                        <th>Jumlah Produk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($games as $game)
                        <tr>
                            <td>{{ $game->name }}</td>
                            <td class="muted mono">{{ $game->slug }}</td>
                            <td>{{ $game->category ?? '-' }}</td>
                            <td>{{ $game->products_count }}</td>
                            <td>
                                @if($game->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-muted">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.games.edit', $game->id) }}" class="btn btn-outline btn-sm">Edit</a>
                                    <form action="{{ route('admin.games.toggle', $game->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">{{ $game->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('admin.games.destroy', $game->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus game ini? Produk top up-nya juga akan terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center muted">Tidak ada game yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $games->links() }}
    </div>
</section>
@endsection
