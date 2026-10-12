{{-- Partial: ikon SVG inline (konsisten di semua OS, tidak bergantung font emoji).
     Pemakaian: @include('partials.icon', ['name' => 'bolt', 'size' => 20]) --}}
@php
    $name = $name ?? 'bolt';
    $size = $size ?? 20;
@endphp
<svg class="icon" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
    @switch($name)
        @case('bolt')
            <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/>
            @break
        @case('shield')
            <path d="M12 22s7.5-3.8 7.5-9.4V5.3L12 2.5 4.5 5.3v7.3C4.5 18.2 12 22 12 22z"/>
            <path d="m9.2 12 2 2 3.6-3.8"/>
            @break
        @case('card')
            <rect x="2.5" y="5" width="19" height="14" rx="2.5"/>
            <path d="M2.5 10h19"/>
            <path d="M6 14.5h3"/>
            @break
        @case('gamepad')
            <rect x="2" y="6.5" width="20" height="11" rx="4.5"/>
            <path d="M6.5 12h3M8 10.5v3"/>
            <path d="M15.5 11.5h.01M18 13.5h.01"/>
            @break
        @case('cart')
            <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 8H6"/>
            <circle cx="9.5" cy="20" r="1.4"/>
            <circle cx="17.5" cy="20" r="1.4"/>
            @break
        @case('bell')
            <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2 7.5-2 7.5h16s-2-1.5-2-7.5z"/>
            <path d="M13.7 20a2 2 0 0 1-3.4 0"/>
            @break
        @case('receipt')
            <path d="M6 3.5h12v17l-2.5-1.6L13 20.5l-1-1.6-1 1.6-2.5-1.6L6 20.5z"/>
            <path d="M9.5 8.5h5M9.5 12h5"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.6"/>
            <path d="M4.8 20.2a7.2 7.2 0 0 1 14.4 0"/>
            @break
        @case('grid')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.6"/>
            <rect x="13.5" y="3.5" width="7" height="7" rx="1.6"/>
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.6"/>
            <rect x="13.5" y="13.5" width="7" height="7" rx="1.6"/>
            @break
        @case('logout')
            <path d="M15 4.5h3.5A1.5 1.5 0 0 1 20 6v12a1.5 1.5 0 0 1-1.5 1.5H15"/>
            <path d="M10 16.5 14.5 12 10 7.5"/>
            <path d="M14.5 12H4"/>
            @break
        @case('chevron-down')
            <path d="m6 9.5 6 6 6-6"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5"/>
            <path d="m16 16 4.5 4.5"/>
            @break
        @case('check')
            <path d="M20 6.5 9.5 17 4 11.5"/>
            @break
        @case('heart')
            <path d="M12 20.5s-7.5-4.4-7.5-9.7A4.3 4.3 0 0 1 12 7.9a4.3 4.3 0 0 1 7.5 2.9c0 5.3-7.5 9.7-7.5 9.7z"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 7.5V12l3 2"/>
            @break
        @default
            <circle cx="12" cy="12" r="9"/>
    @endswitch
</svg>
