@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between']) }}>
    <div class="min-w-0 space-y-2">
        @if (isset($breadcrumb) || !empty($adminBreadcrumbs))
            <div>{{ $breadcrumb ?? '' }}</div>
        @endif
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-text">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-muted">{{ $description }}</p>
            @endif
        </div>
    </div>

    @if (isset($primaryActions) || isset($secondaryActions))
        <div class="flex flex-wrap items-center gap-2">
            @isset($secondaryActions)
                <div class="flex flex-wrap items-center gap-2">{{ $secondaryActions }}</div>
            @endisset
            @isset($primaryActions)
                <div class="flex flex-wrap items-center gap-2">{{ $primaryActions }}</div>
            @endisset
        </div>
    @endif
</div>
