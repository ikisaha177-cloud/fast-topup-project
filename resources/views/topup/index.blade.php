@extends('layouts.app')

@section('title', 'Top Up Game - TopUpMarket')

@section('content')
<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Katalog Game</span>
        <h1>Top Up Game</h1>
        <p>Pilih game, masukkan User ID, dan pilih nominal. Proses otomatis 24/7 tanpa nunggu.</p>

        <div class="banner-stats">
            <div class="banner-stat">
                <strong>{{ $games->count() }}</strong>
                <span>Game tersedia</span>
            </div>
            <div class="banner-stat">
                <strong>{{ $games->sum('products_count') }}</strong>
                <span>Pilihan nominal</span>
            </div>
            <div class="banner-stat">
                <strong>± 12s</strong>
                <span>Rata-rata proses</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        @php $isAdmin = auth()->check() && auth()->user()->isAdmin(); @endphp

        <div class="grid grid-4">
            @forelse($games as $index => $game)
                <div class="card card-hover game-card reveal d{{ ($index % 4) + 1 }}">
                    @if($index === 0)
                        <span class="game-badge is-hot">Terpopuler</span>
                    @elseif($game->products_count >= 4)
                        <span class="game-badge">Populer</span>
                    @endif

                    @include('partials.game-icon', ['game' => $game])
                    <h3 class="card-title">{{ $game->name }}</h3>
                    <p class="muted">{{ $game->category ?? 'Game' }} &middot; {{ $game->products_count }} nominal</p>
                    @if($isAdmin)
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline btn-block mt-16">Kelola Produk</a>
                    @else
                        <a href="{{ route('topup.show', $game->slug) }}" class="btn btn-primary btn-block mt-16">Top Up</a>
                    @endif
                </div>
            @empty
                <div class="empty">Belum ada game aktif.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
