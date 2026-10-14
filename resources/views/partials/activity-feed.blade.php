{{-- Feed "Aktivitas Terbaru": ulasan & pembelian sukses terbaru dengan
     nama pembeli disamarkan. Semua baris berasal dari data nyata â€”
     tidak ada aktivitas yang dikarang, dan kosong = tidak ditampilkan. --}}
@php
    $feedItems = \App\Support\ActivityFeed::items();
@endphp

@if($feedItems)
    <div class="activity-feed">
        <div class="activity-feed-head">
            <span class="eyebrow">Aktivitas Terbaru</span>
            <p class="muted">Pembelian & ulasan terbaru dari pembeli FastTopup â€” nama disamarkan.</p>
        </div>

        <ul class="activity-feed-list">
            @foreach($feedItems as $item)
                <li class="activity-feed-item">
                    <span class="activity-feed-dot" aria-hidden="true"></span>
                    <span class="activity-feed-text">
                        @if($item['url'])
                            <a href="{{ $item['url'] }}"><strong>{{ $item['label'] }}</strong> {{ $item['text'] }}</a>
                        @else
                            <strong>{{ $item['label'] }}</strong> {{ $item['text'] }}
                        @endif
                    </span>
                    <span class="activity-feed-time">{{ $item['time'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

