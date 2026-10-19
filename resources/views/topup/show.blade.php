@extends('layouts.app')

@section('title', 'Top Up ' . $game->name . ' - FastTopup Store')

@section('content')
@push('styles')
<style>
.topup-id-guide{margin:0 0 24px;border:1px solid var(--line-strong);border-radius:var(--radius);background:var(--surface);overflow:hidden}
.topup-id-guide summary{display:flex;align-items:center;gap:12px;padding:16px 18px;cursor:pointer;list-style:none}
.topup-id-guide summary::-webkit-details-marker{display:none}
.topup-id-guide-icon{display:grid;place-items:center;width:34px;height:34px;flex:0 0 auto;border-radius:10px;background:var(--brand-soft);color:var(--brand);font-weight:800}
.topup-id-guide summary strong,.topup-id-guide summary small{display:block}
.topup-id-guide summary small{margin-top:2px;color:var(--muted);font-size:12px}
.topup-id-guide-toggle{margin-left:auto;color:var(--brand);font-size:22px;transition:transform .2s var(--ease)}
.topup-id-guide[open] .topup-id-guide-toggle{transform:rotate(45deg)}
.topup-id-guide-body{padding:0 18px 16px 64px;color:var(--text)}
.topup-id-guide-body p{margin:0}
.topup-id-guide-note{padding-top:10px;color:var(--muted);font-size:13px}
.topup-submitbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:24px;padding:16px;border:1px solid var(--line-strong);border-radius:var(--radius);background:var(--surface-2)}
.topup-selection{display:grid;gap:2px;min-width:0}
.topup-selection-label{color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.topup-selection strong{overflow:hidden;color:var(--ink);text-overflow:ellipsis;white-space:nowrap}
.topup-selection [data-selection-price]{color:var(--brand);font-size:14px;font-weight:700}
.topup-submitbar button{flex:0 0 auto}
@media(max-width:600px){.topup-submitbar{position:sticky;bottom:10px;z-index:5;align-items:stretch;flex-direction:column;gap:12px;margin:16px -8px 0;padding:12px;background:rgba(22,20,29,.96);box-shadow:var(--shadow-lg);backdrop-filter:blur(14px)}.topup-submitbar button{width:100%}.topup-id-guide-body{padding-left:18px}}
@media(prefers-reduced-motion:reduce){.topup-id-guide-toggle{transition:none}}
</style>
@endpush

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

        @php
            $idGuide = collect($game->faqItems())->first(function ($faq) {
                $question = mb_strtolower($faq['question']);
                return str_contains($question, 'di mana') || str_contains($question, 'melihat');
            });
        @endphp

        <details class="topup-id-guide">
            <summary>
                <span class="topup-id-guide-icon" aria-hidden="true">?</span>
                <span><strong>Di mana menemukan User ID?</strong><small>Panduan singkat untuk {{ $game->name }}</small></span>
                <span class="topup-id-guide-toggle" aria-hidden="true">+</span>
            </summary>
            <div class="topup-id-guide-body">
                <p>{{ $idGuide['answer'] ?? 'Buka profil akun di dalam game dan salin User ID yang tertera. Pastikan ID benar sebelum melanjutkan.' }}</p>
                <p class="topup-id-guide-note">Kami hanya membutuhkan ID untuk memproses pesanan. Jangan pernah berikan password akun.</p>
            </div>
        </details>

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
                                       data-product-name="{{ $product->name }}"
                                       data-product-price="{{ number_format($product->price, 0, ',', '.') }}"
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

                    <div class="topup-submitbar">
                        <div class="topup-selection" aria-live="polite" aria-atomic="true">
                            <span class="topup-selection-label">Pilihanmu</span>
                            <strong data-selection-name>Belum ada nominal dipilih</strong>
                            <span data-selection-price>Pilih nominal untuk melihat total</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Lanjut ke Pembayaran</button>
                    </div>
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
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[action="{{ route('topup.checkout.store') }}"]');
    if (!form) return;
    var name = form.querySelector('[data-selection-name]');
    var price = form.querySelector('[data-selection-price]');
    var choices = form.querySelectorAll('input[name="product_id"]');
    function updateSelection() {
        var selected = form.querySelector('input[name="product_id"]:checked');
        if (!selected) {
            name.textContent = 'Belum ada nominal dipilih';
            price.textContent = 'Pilih nominal untuk melihat total';
            return;
        }
        name.textContent = selected.getAttribute('data-product-name');
        price.textContent = 'Total Rp ' + selected.getAttribute('data-product-price');
    }
    choices.forEach(function (choice) { choice.addEventListener('change', updateSelection); });
    updateSelection();
})();
</script>
@endpush
@endsection

