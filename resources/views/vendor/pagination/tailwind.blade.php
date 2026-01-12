@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">
        <div class="pagination-info">
            @if ($paginator->firstItem())
                <span>Affichage de {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} résultats</span>
            @else
                <span>Affichage de {{ $paginator->count() }} résultats</span>
            @endif
        </div>

        <div class="pagination-links">
            {{-- Previous Page Link : masqué lorsque l'on est sur la première page --}}
            @if (!$paginator->onFirstPage())
                <a class="pagination-prev" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span class="pagination-label">Précédent</span>
                </a>
            @else
                <span class="pagination-prev" aria-disabled="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span class="pagination-label">Précédent</span>
                </span>
            @endif

            <div class="pagination-pages" aria-hidden="false">
                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="pagination-ellipsis" aria-disabled="true">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <a class="pagination-page is-current" href="{{ $url }}" aria-current="page">{{ $page }}</a>
                            @else
                                <a class="pagination-page" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a class="pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    <span class="pagination-label">Suivant</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            @else
                <span class="pagination-next" aria-disabled="true">
                    <span class="pagination-label">Suivant</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
            @endif
        </div>

        <span class="pagination-status" aria-label="Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}">
            Page <span class="pagination-current">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
        </span>
    </nav>
@endif
