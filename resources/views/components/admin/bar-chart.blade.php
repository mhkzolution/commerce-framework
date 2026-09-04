@props([
    'series' => [],
    'valueKey' => 'revenue',
    'currency' => 'USD',
    'title' => 'Revenue trend',
    'format' => 'currency',
    'height' => 176,
    'empty' => null,
])

@php
    $values = array_map(fn (array $point): int => (int) ($point[$valueKey] ?? 0), $series ?: [[$valueKey => 0]]);
    $max = max(1, ...$values);
    $empty = $empty ?? __('reports::admin.no_chart_data');
@endphp

<x-admin.card :title="$title">
    @if (count($series) === 0)
        <p class="text-sm text-muted">{{ $empty }}</p>
    @else
        <div class="flex items-end gap-1" style="height: {{ (int) $height + 24 }}px">
            @foreach ($series as $point)
                @php
                    $value = (int) ($point[$valueKey] ?? 0);
                    $barHeight = $value > 0 ? max(4, (int) round(($value / $max) * (int) $height)) : 0;
                    $tooltip = $format === 'number'
                        ? ($point['label'] ?? '').': '.number_format($value)
                        : ($point['label'] ?? '').': '.number_format($value / 100, 2).' '.$currency;
                @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                    @if ($barHeight > 0)
                        <div
                            class="cf-chart-bar w-full max-w-[2.5rem] rounded-t-md transition"
                            style="height: {{ $barHeight }}px"
                            title="{{ $tooltip }}"
                        ></div>
                    @else
                        <div class="w-full max-w-[2.5rem]" style="height: 2px" aria-hidden="true"></div>
                    @endif
                    <span class="w-full truncate text-center text-[10px] text-muted" title="{{ $point['label'] ?? '' }}">
                        {{ $point['label'] ?? '' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</x-admin.card>
