@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">‹ Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
        @endif

        <span class="active"><span>Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span></span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
        @else
            <span aria-disabled="true">Next ›</span>
        @endif
    </nav>
@endif
