@php
    $query = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
    $url = $query->getUrl($item->uuid, $item->isImage() ? 'thumbnail' : null);
    $fullUrl = $query->getUrl($item->uuid);
    $isImage = $item->isImage();
@endphp
<article
    class="cf-media-tile"
    data-media-tile
    data-uuid="{{ $item->uuid }}"
    data-url="{{ $fullUrl }}"
    data-alt="{{ $item->alt_text ?? $item->original_filename }}"
    data-filename="{{ $item->original_filename }}"
    tabindex="0"
>
    <div class="cf-media-tile__thumb">
        @if ($isImage && ($url || $fullUrl))
            <img src="{{ $url ?? $fullUrl }}" alt="{{ $item->alt_text ?? $item->original_filename }}" loading="lazy" decoding="async">
        @else
            <div class="cf-media-tile__file">
                <span>{{ strtoupper($item->media_type) }}</span>
            </div>
        @endif
        <div class="cf-media-tile__overlay" aria-hidden="true"></div>
        <button type="button" class="cf-media-tile__badge" data-tile-check aria-label="{{ __('media::admin.select') }}"></button>
    </div>
    <button type="button" class="cf-media-tile__more" data-open-details aria-label="{{ __('media::admin.open_details') }}">⋯</button>
    <p class="cf-media-tile__name" title="{{ $item->original_filename }}">{{ $item->original_filename }}</p>
    <p class="cf-media-tile__meta">
        {{ number_format($item->size / 1024, 1) }} KB
        @if ($item->width && $item->height)
            · {{ $item->width }}×{{ $item->height }}
        @endif
    </p>
</article>
