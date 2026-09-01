@props([
    'product',
    'visibleAttributes' => [],
])

@php
    $specs = data_get($product->meta, 'specifications', []);
    $hasSpecs = (is_array($specs) && $specs !== []) || $visibleAttributes !== [];
    $hasDescription = filled($product->description);
@endphp

@if ($hasSpecs || $hasDescription)
    <div {{ $attributes->merge(['class' => 'storefront-pdp-details']) }}>
        @if ($hasSpecs)
            <section class="storefront-pdp-details__section storefront-pdp__panel" aria-labelledby="pdp-specs-heading">
                <h2 id="pdp-specs-heading" class="storefront-pdp-details__heading">
                    {{ __('storefront::storefront.section_specifications') }}
                </h2>

                @if (is_array($specs) && $specs !== [])
                    <dl class="storefront-pdp-spec-list">
                        @foreach ($specs as $spec)
                            @if (is_array($spec) && isset($spec['label'], $spec['value']))
                                <div class="storefront-pdp-spec-list__row">
                                    <dt class="storefront-pdp-spec-list__label">{{ $spec['label'] }}</dt>
                                    <dd class="storefront-pdp-spec-list__value">{{ $spec['value'] }}</dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @else
                    <dl class="storefront-pdp-spec-list">
                        @foreach ($visibleAttributes as $attribute)
                            <div class="storefront-pdp-spec-list__row">
                                <dt class="storefront-pdp-spec-list__label">{{ $attribute['label'] }}</dt>
                                <dd class="storefront-pdp-spec-list__value">{{ $attribute['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>
        @endif

        @if ($hasDescription)
            <section class="storefront-pdp-details__section storefront-pdp__panel" aria-labelledby="pdp-description-heading">
                <h2 id="pdp-description-heading" class="storefront-pdp-details__heading">
                    {{ __('storefront::storefront.section_description') }}
                </h2>
                <div class="storefront-pdp-details__prose">{!! $product->description !!}</div>
            </section>
        @endif
    </div>
@endif
