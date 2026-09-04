@props([
    'legend',
    'name',
    'options' => [],
    'selected' => null,
])

<fieldset class="storefront-filters__group" data-filter-collapsible>
    <legend class="storefront-filters__legend">{{ $legend }}</legend>
    <div
        class="storefront-filters__options storefront-filters__options--wrap storefront-filters__options--collapsible"
        data-filter-options
        data-collapsed="true"
    >
        @foreach ($options as $value => $label)
            @php
                $optionValue = is_int($value) ? $label : $value;
                $optionLabel = is_int($value) ? $label : $label;
            @endphp
            <label class="storefront-filters__chip">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" @checked((string) $selected === (string) $optionValue)>
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
    <button type="button" class="storefront-filters__toggle" data-filter-toggle hidden>
        <span data-filter-toggle-more>{{ __('storefront::storefront.filter_show_more') }}</span>
        <span data-filter-toggle-less hidden>{{ __('storefront::storefront.filter_show_less') }}</span>
    </button>
</fieldset>
