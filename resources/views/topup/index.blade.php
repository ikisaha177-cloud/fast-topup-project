@extends('layouts.app')

@section('title', 'Top Up Game - FastTopup Store')

@section('content')
<section class="page-banner">
    @include('partials.starfield', ['compact' => true])

    <div class="container">
        <span class="eyebrow">Katalog Game</span>
        <h1>Top Up Game</h1>
        <p>Pilih game, masukkan User ID, dan pilih nominal. Pesanan diproses setelah pembayaran terverifikasi.</p>

        <div class="banner-stats">
            <div class="banner-stat">
                <strong>{{ $totalGames }}</strong>
                <span>Game tersedia</span>
            </div>
            <div class="banner-stat">
                <strong>{{ $totalNominals }}</strong>
                <span>Pilihan nominal</span>
            </div>
            <div class="banner-stat">
                <strong>{{ $paymentMethodCount }}</strong>
                <span>Metode bayar</span>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="toolbar">
            <form method="GET" action="{{ route('topup.index') }}" class="searchbar">
                <input type="text" name="q" aria-label="Cari game" value="{{ $search }}" placeholder="Cari game favoritmu…">
            </form>
            <div class="chips">
                <a href="{{ route('topup.index', $search ? ['q' => $search] : []) }}"
                   class="chip {{ $category ? '' : 'active' }}" {!! aria_current(! $category, 'true') !!}>Semua</a>
                @foreach($categories as $cat)
                    <a href="{{ route('topup.index', array_filter(['q' => $search, 'kategori' => $cat])) }}"
                       class="chip {{ $category === $cat ? 'active' : '' }}" {!! aria_current($category === $cat, 'true') !!}>{{ $cat }}</a>
                @endforeach
            </div>
        </div>

        <div class="grid grid-4">
            @forelse($games as $index => $game)
                <div class="card card-hover game-card reveal d{{ ($index % 4) + 1 }}" data-tilt>
                    @if($index === 0 && !$search && !$category)
                        <span class="game-badge is-hot">Terpopuler</span>
                    @elseif($game->products_count >= 4)
                        <span class="game-badge">Populer</span>
                    @endif

                    @include('partials.game-icon', ['game' => $game])
                    <h3 class="card-title">{{ $game->name }}</h3>
                    <p class="muted">{{ $game->category ?? 'Game' }} &middot; {{ $game->products_count }} nominal</p>
                    <a href="{{ route('topup.show', $game->slug) }}" class="btn btn-primary btn-block mt-16">Top Up</a>
                </div>
            @empty
                <div class="empty">
                    <span class="empty-icon">@include('partials.icon', ['name' => 'search', 'size' => 34])</span>
                    Tidak ada game yang cocok.
                    @if($search || $category)
                        <div class="mt-16">
                            <a href="{{ route('topup.index') }}" class="btn btn-outline">Hapus Filter</a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</section>
@endsection

