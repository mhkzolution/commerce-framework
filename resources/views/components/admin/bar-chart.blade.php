@props([
    'series' => [],
    'valueKey' => 'revenue',
    'currency' => 'USD',
    'title' => 'Revenue trend',
])

@php
    $max = max(1, ...array_map(fn (array $point): int => (int) ($point[$valueKey] ?? 0), $series ?: [['revenue' => 0]]));
@endphp

<x-admin.card :title="$title">
    @if (count($series) === 0)
        <p class="text-sm text-muted">No data for this period.</p>
    @else
        <div class="flex h-48 items-end gap-1">
            @foreach ($series as $point)
                @php
                    $value = (int) ($point[$valueKey] ?? 0);
                    $height = max(4, (int) round(($value / $max) * 100));
                @endphp
                <div class="group flex flex-1 flex-col items-center justify-end gap-2">
                    <div
                        class="cf-chart-bar w-full rounded-t-md transition"
                        style="height: {{ $height }}%"
                        title="{{ $point['label'] }}: {{ number_format($value / 100, 2) }} {{ $currency }}"
                    ></div>
                    <span class="hidden text-[10px] text-muted sm:block">{{ $point['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-admin.card>
