@if ($paginator->hasPages())
    <nav class="no-print" style="display:flex;gap:8px;align-items:center" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="btn btn-secondary btn-sm" style="opacity:.5;cursor:not-allowed">Previous</span>
        @else
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="btn btn-secondary btn-sm" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span class="btn btn-secondary btn-sm" style="opacity:.5;cursor:not-allowed">Next</span>
        @endif
    </nav>
@endif
