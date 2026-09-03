@props([
    'url' => null,
    'alt' => '',
])

@if (is_string($url) && $url !== '')
    <img src="{{ $url }}" alt="{{ $alt }}" class="h-20 w-20 rounded-lg object-cover bg-surface-muted">
@else
    <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-surface-muted text-muted" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <circle cx="8.5" cy="10" r="1.5" />
            <path d="m21 15-4.5-4.5L9 18" />
        </svg>
    </div>
@endif
