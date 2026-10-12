{{-- Partial: tampilkan logo game. Cari file di public/, lalu storage/app/public/, kalau tidak ada pakai placeholder --}}
@php
    $size = $size ?? 'banner';
    $icon = $game->icon;
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
    <img src="{{ $iconUrl }}" alt="Logo {{ $game->name }}">
</div>
