@if ($paginator->hasPages())
<nav class="admin-pagination" role="navigation" aria-label="Paginación">
    @if ($paginator->onFirstPage())
        <span class="pagination-disabled" aria-disabled="true">Anterior</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
    @endif

    <span class="pagination-pages">
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-dots">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach
    </span>

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
    @else
        <span class="pagination-disabled" aria-disabled="true">Siguiente</span>
    @endif
</nav>
@endif
