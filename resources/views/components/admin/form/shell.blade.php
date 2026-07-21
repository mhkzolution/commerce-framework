@props([
    'title' => null,
    'description' => null,
])

<form {{ $attributes->merge(['class' => 'space-y-6', 'data-admin-form' => true]) }}>
    @if ($title)
        <div>
            <h2 class="text-lg font-semibold text-text">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm text-muted">{{ $description }}</p>
            @endif
        </div>
    @endif

    @isset($tabs)
        <div>{{ $tabs }}</div>
    @endisset

    {{ $slot }}

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">{{ $actions }}</div>
    @endisset
</form>
