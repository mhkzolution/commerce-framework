@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-card shadow-sm']) }}>
    @if ($title || isset($header))
        <div class="border-b border-divider px-5 py-4">
            @if ($title)
                <h2 class="text-sm font-semibold text-text">{{ $title }}</h2>
            @endif
            {{ $header ?? '' }}
        </div>
    @endif
    <div class="p-5">{{ $slot }}</div>
</div>
