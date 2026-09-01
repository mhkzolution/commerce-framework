@props([
    'action',
    'name' => 'sort',
    'value' => null,
    'options' => [],
])

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'storefront-sort-dropdown']) }}>
    {{ $slot }}
    <label class="sr-only" for="{{ $name }}-select">{{ __('storefront::storefront.sort') }}</label>
    <select id="{{ $name }}-select" name="{{ $name }}" class="cf-input storefront-sort-dropdown__select" onchange="this.form.submit()">
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</form>
