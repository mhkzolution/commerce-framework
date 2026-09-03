@if ($paginator->hasPages())
    <nav class="storefront-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="storefront-pagination__list">
            @if ($paginator->onFirstPage())
                <li class="storefront-pagination__item storefront-pagination__item--disabled">
                    <span class="storefront-pagination__link">{{ __('pagination.previous') }}</span>
                </li>
            @else
                <li class="storefront-pagination__item">
                    <a class="storefront-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        {{ __('pagination.previous') }}
                    </a>
                </li>
            @endif

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
                <li class="storefront-pagination__item">
                    <a class="storefront-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        {{ __('pagination.next') }}
                    </a>
                </li>
            @else
                <li class="storefront-pagination__item storefront-pagination__item--disabled">
                    <span class="storefront-pagination__link">{{ __('pagination.next') }}</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
