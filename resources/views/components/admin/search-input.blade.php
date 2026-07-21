@props([
    'placeholder' => 'Search...',
    'name' => 'search',
    'value' => null,
])

<label class="relative block">
    <span class="sr-only">{{ $placeholder }}</span>
    <x-admin.icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value ?? request($name) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'cf-input pl-9']) }}
    >
</label>
