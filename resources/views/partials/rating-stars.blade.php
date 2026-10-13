{{-- Partial: bintang rating 1-5 (dekoratif + label aksesibilitas).
     Pemakaian:
       @include('partials.rating-stars', ['rating' => 4.5])
       @include('partials.rating-stars', ['rating' => $avg, 'count' => 12])

     Bintangnya murni hiasan (aria-hidden); makna angkanya dibawa oleh
     `role="img"` + aria-label, jadi pembaca layar tidak mengeja "bintang
     bintang bintang". Nilai desimal tetap ditampilkan apa adanya di teks,
     jadi bintang bulat di sini tidak pernah menjadi klaim yang menyesatkan. --}}
@php
    $rating = max(0, min(5, (float) ($rating ?? 0)));
    $count  = isset($count) ? (int) $count : null;
    $full   = (int) round($rating);

    $label = $rating > 0
        ? 'Rating ' . number_format($rating, 1, ',', '.') . ' dari 5'
        : 'Belum ada rating';

    if ($count !== null) {
        $label .= ', dari ' . $count . ' ulasan';
    }
@endphp

<span class="rating-stars" role="img" aria-label="{{ $label }}">
    @for($i = 1; $i <= 5; $i++)
        <span class="rating-star {{ $i <= $full ? 'is-on' : '' }}" aria-hidden="true">&#9733;</span>
    @endfor
</span>
