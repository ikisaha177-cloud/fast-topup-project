{{-- Partial: kode QR palsu untuk simulasi pembayaran (deterministik dari kode transaksi) --}}
@php
    $seed = crc32((string) $code);
    mt_srand($seed);
    $size = 21;
@endphp

<svg viewBox="0 0 {{ $size }} {{ $size }}" role="img" aria-label="QR pembayaran {{ $code }}" shape-rendering="crispEdges">
    <rect width="{{ $size }}" height="{{ $size }}" fill="#f4f7ff"/>
    @for ($y = 0; $y < $size; $y++)
        @for ($x = 0; $x < $size; $x++)
            @php
                // Tiga kotak penanda sudut supaya terlihat seperti QR asli.
                $inFinder = ($x < 7 && $y < 7) || ($x > $size - 8 && $y < 7) || ($x < 7 && $y > $size - 8);
                $finderRing = $inFinder && ($x % 7 === 0 || $y % 7 === 0 || ($x % 7 === 3 && $y % 7 === 3));
                $fill = $finderRing || mt_rand(0, 1) === 1;
            @endphp
            @if ($fill)
                <rect x="{{ $x }}" y="{{ $y }}" width="1" height="1" fill="#0b1437"/>
            @endif
        @endfor
    @endfor
</svg>
