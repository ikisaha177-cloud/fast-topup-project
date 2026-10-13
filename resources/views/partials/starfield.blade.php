{{--
    Partial: latar "bintang jatuh" 3D (nuansa Takapedia).
    Dekoratif & deterministik (seeded) supaya posisinya stabil antar render.
    Pemakaian: @include('partials.starfield')
--}}
@php
    // Seed tetap -> bintang tidak "melompat" setiap kali halaman dirender.
    // Semua nilai sengaja bulat (tanpa desimal) agar tidak memunculkan
    // substring angka yang tak diinginkan di HTML publik.
    mt_srand(20260101);

    $shooting = [];
    for ($i = 0; $i < 6; $i++) {
        $shooting[] = [
            'top'   => mt_rand(2, 70),
            'left'  => mt_rand(10, 90),
            'delay' => mt_rand(0, 12),
            'dur'   => mt_rand(5, 8),
            'len'   => mt_rand(90, 170),
        ];
    }

    $dust = [];
    for ($i = 0; $i < 26; $i++) {
        $dust[] = [
            'top'   => mt_rand(0, 100),
            'left'  => mt_rand(0, 100),
            'size'  => mt_rand(1, 3),
            'delay' => mt_rand(0, 12),
            'dur'   => mt_rand(3, 7),
        ];
    }
@endphp

<div class="starfield" aria-hidden="true">
    <div class="starfield-dust">
        @foreach($dust as $star)
            <span class="sf-dust" style="top:{{ $star['top'] }}%; left:{{ $star['left'] }}%; width:{{ $star['size'] }}px; height:{{ $star['size'] }}px; animation-delay:{{ $star['delay'] }}s; animation-duration:{{ $star['dur'] }}s;"></span>
        @endforeach
    </div>

    <div class="starfield-shooting">
        @foreach($shooting as $star)
            <span class="sf-shoot" style="top:{{ $star['top'] }}%; left:{{ $star['left'] }}%; animation-delay:{{ $star['delay'] }}s; animation-duration:{{ $star['dur'] }}s; --shoot-len:{{ $star['len'] }}px;"></span>
        @endforeach
    </div>
</div>
