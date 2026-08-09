{{--
    Pagination accessible.

    Chaque lien annonce le numéro de page en toutes lettres pour les lecteurs
    d'écran, et la page courante porte aria-current="page".
--}}
@if ($paginator->hasPages())
    <nav aria-label="Pagination">
        <ul class="pagination">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="pagination__link" aria-disabled="true">
                        <span aria-hidden="true">‹</span>
                        <span class="visually-hidden">Page précédente (indisponible)</span>
                    </span>
                @else
                    <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <span aria-hidden="true">‹</span>
                        <span class="visually-hidden">Page précédente</span>
                    </a>
                @endif
            </li>

            @foreach ($elements ?? [] as $element)
                @if (is_string($element))
                    <li>
                        <span class="pagination__link" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <a class="pagination__link" href="{{ $url }}" aria-current="page">
                                    <span class="visually-hidden">Page</span>{{ $page }}
                                </a>
                            @else
                                <a class="pagination__link" href="{{ $url }}">
                                    <span class="visually-hidden">Aller à la page</span>{{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <span aria-hidden="true">›</span>
                        <span class="visually-hidden">Page suivante</span>
                    </a>
                @else
                    <span class="pagination__link" aria-disabled="true">
                        <span aria-hidden="true">›</span>
                        <span class="visually-hidden">Page suivante (indisponible)</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
