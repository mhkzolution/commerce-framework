{{--
Temporary adapter for Blog UI Refresh (v1.3.0)

This component intentionally does not depend on
commerce-framework-v1 storefront primitives.

Replace with shared storefront primitives
when the design system is merged.
--}}
@props([
    'action',
    'name' => 'sort',
    'value' => null,
    'options' => [],
])

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'storefront-sort-dropdown']) }}>
    {{ $slot }}
    <label class="sr-only" for="{{ $name }}-select">{{ __('cms::blog.sort') }}</label>
    <select id="{{ $name }}-select" name="{{ $name }}" class="cf-input storefront-sort-dropdown__select" onchange="this.form.submit()">
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</form>
