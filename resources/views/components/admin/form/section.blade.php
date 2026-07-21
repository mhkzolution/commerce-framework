@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-card p-5']) }}>
    <div class="mb-4">
        <h3 class="text-sm font-semibold text-text">{{ $title }}</h3>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>
    <div class="space-y-4">{{ $slot }}</div>
</section>
