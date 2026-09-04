@props([
    'axes' => [],
    'variants' => [],
    'selectedUuid' => null,
])

@php
    $selectedVariant = collect($variants)->firstWhere('uuid', $selectedUuid);
    $selectedOptions = is_array($selectedVariant['options'] ?? null) ? $selectedVariant['options'] : [];

    $resolveVariantForValue = static function (array $variants, string $axisKey, string $value): ?array {
        foreach ($variants as $variant) {
            $options = is_array($variant['options'] ?? null) ? $variant['options'] : [];

            foreach ($options as $optionKey => $optionValue) {
                if (strtolower((string) $optionKey) === strtolower($axisKey) && (string) $optionValue === $value) {
                    return $variant;
                }
            }
        }

        return null;
    };

    $isColorAxis = static function (array $axis): bool {
        $needle = strtolower(($axis['name'] ?? '').' '.($axis['key'] ?? ''));

        return str_contains($needle, 'color') || str_contains($needle, 'สี');
    };
@endphp

@if ($axes !== [] && count($variants) > 1)
    <div {{ $attributes->merge(['class' => 'storefront-variant-axes']) }} data-variant-axes>
        @foreach ($axes as $axis)
            @php
                $axisKey = $axis['key'];
                $selectedValue = null;

                foreach ($selectedOptions as $optionKey => $optionValue) {
                    if (strtolower((string) $optionKey) === strtolower($axisKey)) {
                        $selectedValue = (string) $optionValue;
                        break;
                    }
                }

                $colorAxis = $isColorAxis($axis);
            @endphp

            <fieldset class="storefront-variant-axes__group" data-variant-axis="{{ $axisKey }}">
                <legend class="storefront-variant-axes__legend">{{ $axis['name'] }}</legend>

                <div class="storefront-variant-axes__options {{ $colorAxis ? 'storefront-variant-axes__options--color' : 'storefront-variant-axes__options--text' }}">
                    @foreach ($axis['values'] as $value)
                        @php
                            $matchVariant = $resolveVariantForValue($variants, $axisKey, $value);
                            $isActive = $selectedValue === $value;
                            $isDisabled = $matchVariant === null || ($matchVariant['available'] ?? 0) <= 0;
                            $thumb = $matchVariant['image_thumbnail'] ?? null;
                        @endphp

                        <button
                            type="button"
                            class="storefront-variant-axes__option {{ $colorAxis ? 'storefront-variant-axes__option--color' : 'storefront-variant-axes__option--text' }} {{ $isActive ? 'storefront-variant-axes__option--active' : '' }}"
                            data-variant-axis-value
                            data-axis-key="{{ $axisKey }}"
                            data-axis-value="{{ $value }}"
                            @disabled($isDisabled)
                        >
                            @if ($colorAxis)
                                <span class="storefront-variant-axes__swatch">
                                    @if ($thumb)
                                        <img src="{{ $thumb }}" alt="" class="storefront-variant-axes__swatch-image" loading="lazy">
                                    @endif
                                </span>
                            @endif
                            <span class="storefront-variant-axes__label">{{ $value }}</span>
                        </button>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>
@elseif (count($variants) > 1)
    <div {{ $attributes->merge(['class' => 'storefront-variant-selector']) }} data-variant-selector>
        @foreach ($variants as $variant)
            <button
                type="button"
                class="storefront-variant-selector__option {{ ($variant['uuid'] ?? '') === $selectedUuid ? 'storefront-variant-selector__option--active' : '' }}"
                data-variant-option
                data-variant-uuid="{{ $variant['uuid'] }}"
                @disabled(($variant['available'] ?? 0) <= 0)
            >
                {{ $variant['sku'] ?? $variant['uuid'] }}
            </button>
        @endforeach
    </div>
@endif
