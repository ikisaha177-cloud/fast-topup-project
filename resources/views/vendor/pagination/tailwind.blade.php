@if ($paginator->hasPages())
    <div class="pagination">
        <nav aria-label="Pagination">
            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span aria-hidden="true" style="opacity:.4; padding:0 8px;">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
            @endif

            {{-- Halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span style="opacity:.5; padding:0 4px;">…</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
            @else
                <span aria-hidden="true" style="opacity:.4; padding:0 8px;">›</span>
            @endif
        </nav>
    </div>
@endif
