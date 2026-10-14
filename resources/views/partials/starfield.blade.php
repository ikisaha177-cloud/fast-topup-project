{{--
    Partial: latar "bintang jatuh" 3D (nuansa Takapedia).
    Dekoratif & deterministik (seeded) supaya posisinya stabil antar render.

    Pemakaian:
      @include('partials.starfield')              -> versi penuh (hero)
      @include('partials.starfield', ['compact' => true]) -> versi ringkas (banner)

    Catatan arah: setiap streak dibungkus pembungkus yang di-rotate sesuai sudut
    lintasannya, lalu streak-nya bergerak lurus di sumbu lokal. Jadi garis cahaya
    SELALU sejajar dengan arah geraknya (bukan nyerong) dan kepala yang terang
    berada di depan.
--}}
@php
    $compact = $compact ?? false;

    // Seed tetap -> bintang tidak "melompat" setiap kali halaman dirender.
    mt_srand($compact ? 701 : 20260101);

    $shootCount = $compact ? 4 : 8;
    $shooting = [];
    for ($i = 0; $i < $shootCount; $i++) {
        $shooting[] = [
            'top'   => mt_rand(4, 62),
            'left'  => mt_rand(26, 96),
            'angle' => mt_rand(128, 158),   // arah turun-ke-kiri
            'len'   => mt_rand(110, 190),
            'dist'  => mt_rand(320, 520),
            'dur'   => mt_rand(5, 8),       // detik (bulat, tanpa desimal)
            'delay' => mt_rand(0, 9),
        ];
    }

    // Tiga lapisan debu bintang untuk kedalaman (parallax saat kursor bergerak).
    $layers = [
        ['key' => 'far',  'count' => $compact ? 14 : 22, 'size' => 1, 'depth' => 1],
        ['key' => 'mid',  'count' => $compact ? 10 : 16, 'size' => 2, 'depth' => 2],
        ['key' => 'near', 'count' => $compact ? 6 : 10,  'size' => 3, 'depth' => 4],
    ];

    $dust = [];
    foreach ($layers as $layer) {
        $dust[$layer['key']] = [
            'size'  => $layer['size'],
            'depth' => $layer['depth'],
            'stars' => [],
        ];

        for ($i = 0; $i < $layer['count']; $i++) {
            $dust[$layer['key']]['stars'][] = [
                'top'   => mt_rand(0, 100),
                'left'  => mt_rand(0, 100),
                'delay' => mt_rand(0, 9),
                'dur'   => mt_rand(3, 7),
                'warm'  => mt_rand(0, 4) === 0,
            ];
        }
    }
@endphp

<div class="starfield{{ $compact ? ' starfield-compact' : '' }}" aria-hidden="true">
    @foreach($dust as $layer)
        <div class="starfield-layer" style="--depth:{{ $layer['depth'] }}">
            @foreach($layer['stars'] as $star)
                <span class="sf-dust{{ $star['warm'] ? ' is-warm' : '' }}"
                      style="top:{{ $star['top'] }}%; left:{{ $star['left'] }}%; width:{{ $layer['size'] }}px; height:{{ $layer['size'] }}px; animation-delay:{{ $star['delay'] }}s; animation-duration:{{ $star['dur'] }}s;"></span>
            @endforeach
        </div>
    @endforeach

    <div class="starfield-shooting">
        @foreach($shooting as $star)
            <span class="sf-shoot"
                  style="top:{{ $star['top'] }}%; left:{{ $star['left'] }}%; --angle:{{ $star['angle'] }}deg; --len:{{ $star['len'] }}px; --dist:{{ $star['dist'] }}px; animation-delay:{{ $star['delay'] }}s; animation-duration:{{ $star['dur'] }}s;"></span>
        @endforeach
    </div>
</div>
