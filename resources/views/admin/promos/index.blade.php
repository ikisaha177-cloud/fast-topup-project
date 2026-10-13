@extends('layouts.app')

@section('title', 'Kelola Kode Promo - Admin')

@section('content')
<section class="section">
    <div class="container">
        <div class="page-head">
            <div>
                <h1 class="section-title">Kode Promo</h1>
                <p class="section-subtitle mb-0">Potongan harga untuk checkout top up dan marketplace.</p>
            </div>
            <a href="{{ route('admin.promos.create') }}" class="btn btn-primary">+ Tambah Kode Promo</a>
        </div>

        @include('partials.admin-nav')

        <div class="toolbar">
            <form method="GET" action="{{ route('admin.promos.index') }}" class="searchbar">
                <input type="text" name="q" aria-label="Cari kode promo" value="{{ $search }}" placeholder="Cari kode atau deskripsi…">
            </form>
        </div>

        <div class="card table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Potongan</th>
                        <th scope="col">Cakupan</th>
                        <th scope="col">Syarat</th>
                        <th scope="col">Terpakai</th>
                        <th scope="col">Berlaku</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promos as $promo)
                        <tr>
                            <td>
                                <span class="mono">{{ $promo->code }}</span>
                                @if($promo->description)
                                    <span class="item-sub">{{ $promo->description }}</span>
                                @endif
                            </td>
                            <td>{{ $promo->valueLabel() }}</td>
                            <td>{{ $promo->scopeLabel() }}</td>
                            <td class="muted">
                                @if($promo->min_spend > 0)
                                    Min. Rp {{ number_format($promo->min_spend, 0, ',', '.') }}
                                    @if($promo->max_discount !== null)
                                        <br>maks. Rp {{ number_format($promo->max_discount, 0, ',', '.') }}
                                    @endif
                                @elseif($promo->max_discount !== null)
                                    Maks. Rp {{ number_format($promo->max_discount, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                {{ $promo->used_count }}<span class="item-sub">{{ $promo->usage_limit ? ' / ' . $promo->usage_limit : ' tanpa batas' }}</span>
                            </td>
                            <td class="muted">
                                @if($promo->starts_at)
                                    {{ $promo->starts_at->format('d-m-Y H:i') }}
                                @else
                                    sejak awal
                                @endif
                                <br>
                                @if($promo->ends_at)
                                    s/d {{ $promo->ends_at->format('d-m-Y H:i') }}
                                @else
                                    tanpa batas
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $promo->statusBadge() }}">{{ $promo->statusLabel() }}</span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.promos.edit', $promo->id) }}" class="btn btn-outline btn-sm">Edit</a>
                                    <form action="{{ route('admin.promos.toggle', $promo->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">{{ $promo->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <form action="{{ route('admin.promos.destroy', $promo->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus kode promo ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center muted">Belum ada kode promo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $promos->links() }}
    </div>
</section>
@endsection
