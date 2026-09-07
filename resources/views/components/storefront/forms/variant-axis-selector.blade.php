@props([
    'axes' => [],
    'variants' => [],
    'selectedUuid' => null,
])

@php
    $selectedVariant = collect($variants)->firstWhere('uuid', $selectedUuid);
    $selectedOptions = is_array($selectedVariant['options'] ?? null) ? $selectedVariant['options'] : [];

    $variantsForValue = static function (array $variants, string $axisKey, string $value): array {
        $matches = [];

        foreach ($variants as $variant) {
            $options = is_array($variant['options'] ?? null) ? $variant['options'] : [];

            foreach ($options as $optionKey => $optionValue) {
                if (strtolower((string) $optionKey) === strtolower($axisKey) && (string) $optionValue === $value) {
                    $matches[] = $variant;
                    break;
                }
            }
        }

        return $matches;
    };

    $variantIsInStock = static fn (array $variant): bool => $variant['in_stock']
        ?? (($variant['available'] ?? 0) > 0);

    $optionValue = static function (array $options, string $axisKey): ?string {
        foreach ($options as $optionKey => $optionValue) {
            if (strtolower((string) $optionKey) === strtolower($axisKey)) {
                return (string) $optionValue;
            }
        }

        return null;
    };

    $combinationExists = static function (array $variants, array $selections) use ($optionValue): bool {
        foreach ($variants as $variant) {
            $options = is_array($variant['options'] ?? null) ? $variant['options'] : [];
            $matches = true;

            foreach ($selections as $key => $value) {
                if ($optionValue($options, (string) $key) !== (string) $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return true;
            }
        }

        return false;
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
                $selectedValue = $optionValue($selectedOptions, (string) $axisKey);
                $colorAxis = $isColorAxis($axis);
            @endphp

            <fieldset class="storefront-variant-axes__group" data-variant-axis="{{ $axisKey }}">
                <legend class="storefront-variant-axes__legend">{{ $axis['name'] }}</legend>

                <div class="storefront-variant-axes__options {{ $colorAxis ? 'storefront-variant-axes__options--color' : 'storefront-variant-axes__options--text' }}">
                    @foreach ($axis['values'] as $value)
                        @php
                            $matchingVariants = $variantsForValue($variants, $axisKey, $value);
                            $matchVariant = collect($matchingVariants)->first($variantIsInStock)
                                ?? ($matchingVariants[0] ?? null);
                            $isActive = $selectedValue === $value;
                            $trial = [];
                            foreach ($selectedOptions as $selectedKey => $selectedOptionValue) {
                                $trial[strtolower((string) $selectedKey)] = (string) $selectedOptionValue;
                            }
                            $trial[strtolower((string) $axisKey)] = (string) $value;
                            $isDisabled = ! $combinationExists($variants, $trial);
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
                @disabled(! ($variant['in_stock'] ?? (($variant['available'] ?? 0) > 0)))
            >
                {{ $variant['sku'] ?? $variant['uuid'] }}
            </button>
        @endforeach
    </div>
@endif
