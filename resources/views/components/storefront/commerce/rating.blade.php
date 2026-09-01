@props([
    'rating',
    'count' => null,
])

@if ($rating !== null && (float) $rating > 0)
    <div {{ $attributes->merge(['class' => 'storefront-rating']) }} aria-label="{{ __('storefront::storefront.rating_label', ['rating' => number_format((float) $rating, 1)]) }}">
        <span class="storefront-rating__stars" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
                <span class="storefront-rating__star {{ $i <= round((float) $rating) ? 'storefront-rating__star--filled' : '' }}">★</span>
            @endfor
        </span>
        @if ($count)
            <span class="storefront-rating__count">({{ $count }})</span>
        @endif
    </div>
@endif
