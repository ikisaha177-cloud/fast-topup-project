@extends('layouts.app')

@section('title', 'Top Up ' . $game->name . ' - FastTopup Store')

@section('content')
<section class="section">
    <div class="container">
        <p class="breadcrumb">
            <a href="{{ route('topup.index') }}">Top Up Game</a> / {{ $game->name }}
        </p>

        <div class="card" style="display:flex; align-items:center; gap:22px; margin-bottom:26px; flex-wrap:wrap;">
            @include('partials.game-icon', ['game' => $game, 'size' => 'sm'])
            <div style="flex:1 1 240px;">
                <span class="eyebrow" style="margin-bottom:4px;">Top Up Game</span>
                <h1 class="mb-0" style="font-size:26px;">{{ $game->name }}</h1>
                <span class="muted">{{ $game->category ?? 'Game' }} &middot; {{ $products->count() }} nominal tersedia</span>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                @if(\App\Support\Contact::whatsappUrl())
                    <a class="btn btn-outline btn-sm"
                       href="{{ \App\Support\Contact::whatsappUrl('Saya mau tanya top up ' . $game->name . '.') }}"
                       target="_blank" rel="noopener">Chat CS WhatsApp</a>
                @endif
                <span class="badge badge-info badge-plain">@include('partials.icon', ['name' => 'bolt', 'size' => 13]) Verifikasi Cepat</span>
                <span class="badge badge-success badge-plain">@include('partials.icon', ['name' => 'shield', 'size' => 13]) Aman</span>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="empty">
                <span class="empty-icon">@include('partials.icon', ['name' => 'gamepad', 'size' => 34])</span>
                Belum ada nominal top up untuk game ini.
            </div>
        @else
            <form action="{{ route('topup.checkout.store') }}" method="POST">
                @csrf
                <input type="hidden" name="game_slug" value="{{ $game->slug }}">

                <div class="card" style="margin-bottom:24px;">
                    <h3 class="mb-0" style="font-size:17px;">1. Masukkan Data Akun</h3>
                    <p class="muted mt-8" style="margin-bottom:18px;">Pastikan User ID benar agar item masuk ke akun yang tepat.</p>

                    <div class="grid grid-2">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="user_game_id">User ID <span style="color:var(--red);">*</span></label>
                            <input type="text" id="user_game_id" name="user_game_id" class="form-control"
                                   value="{{ old('user_game_id') }}" placeholder="Contoh: 12345678" required>
                            <div class="form-hint">Bisa dilihat di menu profil akun game kamu.</div>
                            @error('user_game_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="server_id">Server ID (opsional)</label>
                            <input type="text" id="server_id" name="server_id" class="form-control"
                                   value="{{ old('server_id') }}" placeholder="Isi jika game membutuhkan server">
                            <div class="form-hint">Contoh: Zone 1. Kosongkan bila tidak diminta.</div>
                            @error('server_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="mb-0" style="font-size:17px;">2. Pilih Nominal</h3>
                    <p class="muted mt-8" style="margin-bottom:18px;">Pilih nominal yang ingin kamu top up.</p>

                    @error('product_id') <div class="alert alert-error">{{ $message }}</div> @enderror

                    <div class="nominal-grid">
                        @foreach($products as $product)
                            @php $habis = $product->stock < 1; @endphp

                            <label class="nominal-item {{ $habis ? 'is-empty' : '' }}">
                                <input type="radio" name="product_id" value="{{ $product->id }}"
                                       {{ $habis ? 'disabled' : '' }}
                                       {{ ! $habis && (string) old('product_id') === (string) $product->id ? 'checked' : '' }}
                                       required>
                                <span class="nominal-box">
                                    <span class="nominal-name">{{ $product->name }}</span>
                                    <span class="nominal-price">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                    <span class="nominal-stock">{{ $habis ? 'Stok habis' : 'Stok: ' . $product->stock }}</span>
                                    <span class="nominal-choose">{{ $habis ? 'Habis' : 'Pilih' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg mt-24">Lanjut ke Pembayaran â†’</button>
                </div>
            </form>
        @endif

        @if($game->faqItems())
            <div style="margin-top:34px;" id="faq">
                <span class="eyebrow">FAQ {{ $game->name }}</span>
                <h2 class="mb-0" style="font-size:22px;">Pertanyaan umum seputar {{ $game->name }}</h2>

                <div class="faq" style="margin-top:16px;">
                    @foreach($game->faqItems() as $index => $faq)
                        <details class="faq-item" {{ $index === 0 ? 'open' : '' }}>
                            <summary>{{ $faq['question'] }}</summary>
                            <div class="faq-body">{{ $faq['answer'] }}</div>
                        </details>
                    @endforeach
                </div>

                @if(\App\Support\Contact::whatsappUrl())
                    <p class="muted" style="margin-top:14px;">
                        Masih ada yang ingin ditanyakan?
                        <a href="{{ \App\Support\Contact::whatsappUrl('Saya punya pertanyaan tentang top up ' . $game->name . '.') }}"
                           target="_blank" rel="noopener">Chat CS WhatsApp kami</a>.
                    </p>
                @endif
            </div>
        @endif
    </div>
</section>
@endsection

