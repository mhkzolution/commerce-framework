@php
    $query = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
    $url = $query->getUrl($item->uuid, $item->isImage() ? 'thumbnail' : null);
    $fullUrl = $query->getUrl($item->uuid);
    $isImage = $item->isImage();
@endphp
<tr
    class="cf-media-row"
    data-media-row
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
    <td class="cf-media-row__check">
        <button type="button" class="cf-media-tile__badge" data-tile-check aria-label="{{ __('media::admin.select') }}"></button>
    </td>
    <td class="cf-media-row__preview">
        @if ($isImage && ($url || $fullUrl))
            <img src="{{ $url ?? $fullUrl }}" alt="{{ $item->alt_text ?? $item->original_filename }}" loading="lazy" decoding="async">
        @else
            <span class="cf-media-row__file">{{ strtoupper($item->media_type) }}</span>
        @endif
    </td>
    <td class="cf-media-row__name" title="{{ $item->original_filename }}">{{ $item->original_filename }}</td>
    <td>{{ $item->mime_type }}</td>
    <td>{{ $item->width && $item->height ? $item->width.'×'.$item->height : '—' }}</td>
    <td>{{ number_format($item->size / 1024, 1) }} KB</td>
    <td>{{ $item->folder?->name ?? __('media::admin.unfiled') }}</td>
    <td>{{ $item->created_at?->timezone(config('app.timezone'))->format('M j, Y H:i') }}</td>
</tr>
