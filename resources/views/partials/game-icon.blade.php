{{-- Partial: tampilkan logo game. Cari file di public/, lalu storage/app/public/, kalau tidak ada pakai placeholder.
     Null-safe: game yang sudah dihapus tetap menampilkan placeholder.
     Pemakaian: @include('partials.game-icon', ['game' => $game, 'size' => 'xs']) --}}
@php
    $size = $size ?? 'banner';
    $icon = $game->icon ?? null;
    $label = $game->name ?? ($name ?? 'Game');
    $publicExists = $icon && file_exists(public_path($icon));
    $storageExists = $icon && file_exists(storage_path('app/public/' . $icon));

    if ($publicExists) {
        $iconUrl = asset($icon);
    } elseif ($storageExists) {
        $iconUrl = asset('storage/' . $icon);
    } else {
        $iconUrl = asset('images/games/placeholder-game.svg');
    }
@endphp

<div class="game-logo game-logo-{{ $size }}">
    <img src="{{ $iconUrl }}" alt="Logo {{ $label }}"
         width="480" height="300" loading="lazy" decoding="async">
</div>
