@php
    $query = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
    $url = $query->getUrl($item->uuid, $item->isImage() ? 'thumbnail' : null);
    $fullUrl = $query->getUrl($item->uuid);
    $isImage = $item->isImage();
    $uploaded = $item->created_at?->timezone(config('app.timezone'))->format('M j, Y');
@endphp
<article
    class="cf-media-tile"
    data-media-tile
    data-uuid="{{ $item->uuid }}"
    data-url="{{ $fullUrl }}"
    data-alt="{{ $item->alt_text ?? $item->original_filename }}"
    data-filename="{{ $item->original_filename }}"
    data-mime="{{ $item->mime_type }}"
    data-type="{{ $item->media_type }}"
    data-size="{{ $item->size }}"
    data-width="{{ $item->width }}"
    data-height="{{ $item->height }}"
    data-folder="{{ $item->folder?->name }}"
    data-created="{{ $item->created_at?->toIso8601String() }}"
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
        <div class="cf-media-tile__actions">
            <button type="button" data-tile-preview aria-label="{{ __('media::admin.preview') }}">{{ __('media::admin.preview') }}</button>
            <button type="button" data-tile-copy aria-label="{{ __('media::admin.copy_url') }}">{{ __('media::admin.copy_url') }}</button>
            <button type="button" data-tile-edit aria-label="{{ __('media::admin.edit') }}">{{ __('media::admin.edit') }}</button>
            <button type="button" data-tile-download aria-label="{{ __('media::admin.download') }}">{{ __('media::admin.download') }}</button>
            @if ($canDelete)
                <button type="button" data-tile-delete aria-label="{{ __('media::admin.delete') }}">{{ __('media::admin.delete') }}</button>
            @endif
        </div>
    </div>
    <p class="cf-media-tile__name" title="{{ $item->original_filename }}">{{ $item->original_filename }}</p>
    <p class="cf-media-tile__meta">
        @if ($item->width && $item->height)
            {{ $item->width }}×{{ $item->height }} ·
        @endif
        {{ number_format($item->size / 1024, 1) }} KB
        @if ($uploaded)
            · {{ $uploaded }}
        @endif
    </p>
</article>
