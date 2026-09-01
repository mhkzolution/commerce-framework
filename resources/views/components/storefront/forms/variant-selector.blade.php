@props([
    'variants' => [],
    'selectedUuid' => null,
])

@if (count($variants) > 1)
    <fieldset class="storefront-variant-selector" data-variant-selector>
        <legend class="storefront-variant-selector__legend">{{ __('storefront::storefront.select_variant') }}</legend>
        <div class="storefront-variant-selector__options">
            @foreach ($variants as $variant)
                <button
                    type="button"
                    class="storefront-variant-selector__option {{ $selectedUuid === $variant['uuid'] ? 'storefront-variant-selector__option--active' : '' }}"
                    data-variant-option
                    data-variant-uuid="{{ $variant['uuid'] }}"
                    @disabled($variant['available'] <= 0)
                >
                    <span class="storefront-variant-selector__name">{{ $variant['name'] }}</span>
                    @if ($variant['available'] <= 0)
                        <span class="storefront-variant-selector__status">{{ __('storefront::storefront.out_of_stock') }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </fieldset>
@endif
