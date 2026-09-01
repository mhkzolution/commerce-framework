@props([
    'series' => [],
    'primaryKey' => 'ordered',
    'secondaryKey' => 'received',
    'primaryLabel' => 'Ordered',
    'secondaryLabel' => 'Received',
    'title' => 'Units trend',
])

@php
    $max = max(1, ...array_map(
        fn (array $point): int => max((int) ($point[$primaryKey] ?? 0), (int) ($point[$secondaryKey] ?? 0)),
        $series ?: [[$primaryKey => 0, $secondaryKey => 0]],
    ));
@endphp

<x-admin.card :title="$title">
    @if (count($series) === 0)
        <p class="text-sm text-muted">No data for this period.</p>
    @else
        <div class="mb-3 flex items-center gap-4 text-xs text-muted">
            <span class="inline-flex items-center gap-2"><span class="inline-block h-2 w-2 rounded-full bg-accent"></span>{{ $primaryLabel }}</span>
            <span class="inline-flex items-center gap-2"><span class="inline-block h-2 w-2 rounded-full bg-text-secondary"></span>{{ $secondaryLabel }}</span>
        </div>
        <div class="flex h-48 items-end gap-2">
            @foreach ($series as $point)
                @php
                    $primary = (int) ($point[$primaryKey] ?? 0);
                    $secondary = (int) ($point[$secondaryKey] ?? 0);
                    $primaryHeight = max(4, (int) round(($primary / $max) * 100));
                    $secondaryHeight = max(4, (int) round(($secondary / $max) * 100));
                @endphp
                <div class="flex flex-1 flex-col items-center justify-end gap-2">
                    <div class="flex w-full items-end justify-center gap-0.5">
                        <div
                            class="cf-chart-bar w-1/2 rounded-t-md bg-accent"
                            style="height: {{ $primaryHeight }}%"
                            title="{{ $point['label'] }} {{ $primaryLabel }}: {{ number_format($primary) }}"
                        ></div>
                        <div
                            class="cf-chart-bar w-1/2 rounded-t-md bg-text-secondary/60"
                            style="height: {{ $secondaryHeight }}%"
                            title="{{ $point['label'] }} {{ $secondaryLabel }}: {{ number_format($secondary) }}"
                        ></div>
                    </div>
                    <span class="hidden text-[10px] text-muted sm:block">{{ $point['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</x-admin.card>
