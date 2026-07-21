@props(['items' => []])

@php
    $items = count($items) ? $items : ($adminBreadcrumbs ?? []);
@endphp

@if (count($items))
    <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-muted">
        @foreach ($items as $item)
            @if (!empty($item['url']) && empty($item['active']))
                <a href="{{ $item['url'] }}" class="hover:text-text">{{ $item['label'] }}</a>
            @else
                <span @class(['text-text' => !empty($item['active'])])>{{ $item['label'] }}</span>
            @endif
            @unless ($loop->last)
                <span>/</span>
            @endunless
        @endforeach
    </nav>
@endif
