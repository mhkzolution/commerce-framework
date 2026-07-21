<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
    <div class="flex flex-1 flex-wrap items-center gap-2">
        {{ $filters ?? '' }}
        {{ $search ?? '' }}
    </div>
    <div class="flex flex-wrap items-center gap-2">
        {{ $views ?? '' }}
        {{ $columns ?? '' }}
        {{ $export ?? '' }}
        {{ $actions ?? '' }}
    </div>
</div>
