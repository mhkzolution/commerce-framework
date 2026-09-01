@props([
    'author',
    'rating',
    'body',
    'date' => null,
    'title' => null,
])

<article {{ $attributes->merge(['class' => 'storefront-review-card']) }}>
    <header class="storefront-review-card__header">
        <div>
            @if ($title)
                <h3 class="storefront-review-card__title">{{ $title }}</h3>
            @endif
            <p class="storefront-review-card__author">{{ $author }}</p>
        </div>
        <x-storefront.commerce.rating :rating="$rating" />
    </header>

    <p class="storefront-review-card__body">{{ $body }}</p>

    @if ($date)
        <time class="storefront-review-card__date">{{ $date }}</time>
    @endif
</article>
