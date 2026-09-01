@props([
    'product',
    'tabs' => [],
    'visibleAttributes' => [],
])

@if (count($tabs) > 0)
    <section {{ $attributes->merge(['class' => 'storefront-product-tabs']) }} data-product-tabs>
        <div class="storefront-product-tabs__nav" role="tablist">
            @foreach ($tabs as $index => $tab)
                <button
                    type="button"
                    role="tab"
                    id="tab-{{ $tab['id'] }}"
                    class="storefront-product-tabs__tab {{ $index === 0 ? 'storefront-product-tabs__tab--active' : '' }}"
                    data-tab-trigger="{{ $tab['id'] }}"
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                    aria-controls="panel-{{ $tab['id'] }}"
                >
                    {{ __('storefront::storefront.tab_' . $tab['label']) }}
                </button>
            @endforeach
        </div>

        @foreach ($tabs as $index => $tab)
            <div
                id="panel-{{ $tab['id'] }}"
                role="tabpanel"
                class="storefront-product-tabs__panel {{ $index === 0 ? 'storefront-product-tabs__panel--active' : '' }}"
                data-tab-panel="{{ $tab['id'] }}"
                aria-labelledby="tab-{{ $tab['id'] }}"
                @if ($index !== 0) hidden @endif
            >
                @switch($tab['id'])
                    @case('description')
                        <div class="storefront-product-tabs__prose">{!! nl2br(e($product->description)) !!}</div>
                        @break

                    @case('specifications')
                        @php
                            $specs = data_get($product->meta, 'specifications', []);
                        @endphp
                        @if (is_array($specs) && $specs !== [])
                            <dl class="storefront-spec-list">
                                @foreach ($specs as $spec)
                                    @if (is_array($spec) && isset($spec['label'], $spec['value']))
                                        <div class="storefront-spec-list__row">
                                            <dt>{{ $spec['label'] }}</dt>
                                            <dd>{{ $spec['value'] }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        @else
                            <x-storefront.attribute-list :items="$visibleAttributes" />
                        @endif
                        @break

                    @case('reviews')
                        @php
                            $reviews = data_get($product->meta, 'reviews', []);
                            $rating = data_get($product->meta, 'rating');
                            $reviewCount = data_get($product->meta, 'review_count');
                        @endphp
                        <div class="storefront-reviews">
                            <x-storefront.rating :rating="$rating" :count="$reviewCount" />
                            @if (is_array($reviews))
                                @foreach ($reviews as $review)
                                    @if (is_array($review))
                                        <article class="storefront-reviews__item">
                                            @if (! empty($review['author']))
                                                <p class="storefront-reviews__author">{{ $review['author'] }}</p>
                                            @endif
                                            @if (isset($review['rating']))
                                                <x-storefront.rating :rating="$review['rating']" />
                                            @endif
                                            @if (! empty($review['body']))
                                                <p class="storefront-reviews__body">{{ $review['body'] }}</p>
                                            @endif
                                        </article>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                        @break

                    @case('faq')
                        <div class="storefront-faq">
                            @foreach (data_get($product->meta, 'faq', []) as $item)
                                @if (is_array($item) && isset($item['q'], $item['a']))
                                    <details class="storefront-faq__item">
                                        <summary class="storefront-faq__question">{{ $item['q'] }}</summary>
                                        <p class="storefront-faq__answer">{{ $item['a'] }}</p>
                                    </details>
                                @endif
                            @endforeach
                        </div>
                        @break

                    @case('downloads')
                        <ul class="storefront-downloads">
                            @foreach (data_get($product->meta, 'downloads', []) as $download)
                                @if (is_array($download) && ! empty($download['url']))
                                    <li>
                                        <a href="{{ $download['url'] }}" class="storefront-downloads__link" target="_blank" rel="noopener">
                                            {{ $download['label'] ?? __('storefront::storefront.download') }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        @break
                @endswitch
            </div>
        @endforeach
    </section>
@endif
