@if ($paginator->hasPages())
    <nav class="storefront-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="storefront-pagination__list">
            @unless ($paginator->onFirstPage())
                <li class="storefront-pagination__item storefront-pagination__item--prev">
                    <a
                        class="storefront-pagination__link"
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        aria-label="{{ __('storefront::storefront.previous_page') }}"
                    >‹</a>
                </li>
            @endunless

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="storefront-pagination__item storefront-pagination__item--disabled">
                        <span class="storefront-pagination__link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="storefront-pagination__item {{ $page == $paginator->currentPage() ? 'storefront-pagination__item--current' : '' }}">
                            @if ($page == $paginator->currentPage())
                                <span class="storefront-pagination__link storefront-pagination__link--current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="storefront-pagination__link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="storefront-pagination__item storefront-pagination__item--next">
                    <a
                        class="storefront-pagination__link"
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        aria-label="{{ __('storefront::storefront.next_page') }}"
                    >›</a>
                </li>
            @endif
        </ul>
    </nav>
@endif
