@php
    $previewType = old('popup_type', $item?->popup_type ?? 'image');
    $previewHeadline = trim((string) old('headline', $item?->headline ?? ''));
    $previewSubheadline = trim((string) old('subheadline', $item?->subheadline ?? ''));
    $previewButtonText = trim((string) old('button_text', $item?->button_text ?? ''));
    $previewClosable = (bool) old('closable', $item?->closable ?? true);
    $previewActive = (bool) old('is_active', $item?->is_active ?? true);
    $previewStatus = old('status', $item?->status ?? 'draft');
    $previewDelay = (int) old('show_delay', $item?->show_delay ?? 1);
    $previewAutoClose = old('auto_close', $item?->auto_close);
    $hasCopy = $previewHeadline !== '' || $previewSubheadline !== '' || $previewButtonText !== '';
    $isImageOnly = $previewType === 'image' || ! $hasCopy;

    $previewImageUrl = null;
    $imageUuid = old('image_media_uuid', $item?->image_media_uuid);
    if (is_string($imageUuid) && $imageUuid !== '' && app()->bound(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)) {
        $media = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
        $previewImageUrl = $media->getUrl($imageUuid) ?? $media->getUrl($imageUuid, 'thumbnail');
    }
@endphp

<aside
    class="cms-popup-workspace__preview"
    data-popup-preview
    data-status-draft="{{ __('cms::admin.status_draft') }}"
    data-status-published="{{ __('cms::admin.status_published') }}"
    data-active-label="{{ __('cms::admin.active') }}"
    data-inactive-label="{{ __('cms::admin.inactive') }}"
    data-delay-template="{{ __('cms::admin.popup_preview_delay', ['seconds' => ':seconds']) }}"
    data-auto-close-template="{{ __('cms::admin.popup_preview_auto_close', ['seconds' => ':seconds']) }}"
>
    <div class="cms-popup-preview__header">
        <h3 class="cms-popup-preview__title">{{ __('cms::admin.popup_preview') }}</h3>
        <p class="cms-popup-preview__hint">{{ __('cms::admin.popup_preview_hint') }}</p>
        <div class="cms-popup-preview__chips">
            <span class="cms-popup-preview__chip" data-preview-status>
                {{ $previewStatus === 'published' ? __('cms::admin.status_published') : __('cms::admin.status_draft') }}
            </span>
            <span class="cms-popup-preview__chip{{ $previewActive ? '' : ' is-off' }}" data-preview-active>
                {{ $previewActive ? __('cms::admin.active') : __('cms::admin.inactive') }}
            </span>
        </div>
    </div>

    <div class="cms-popup-preview__stage" aria-hidden="true">
        <div class="cms-popup-preview__dialog">
            <div class="cms-popup-preview__frame">
                <article
                    class="cms-popup-preview__slide is-type-{{ $previewType }}{{ $isImageOnly ? ' is-image-only' : '' }}"
                    data-preview-slide
                >
                    <div class="cms-popup-preview__media" data-preview-media>
                        <img
                            alt=""
                            data-preview-image
                            @if ($previewImageUrl) src="{{ $previewImageUrl }}" @else hidden @endif
                        >
                        <span class="cms-popup-preview__placeholder" data-preview-placeholder @if ($previewImageUrl) hidden @endif>
                            {{ __('cms::admin.popup_preview_empty_image') }}
                        </span>
                    </div>
                    <div class="cms-popup-preview__copy" data-preview-copy @if ($isImageOnly) hidden @endif>
                        <h2 class="cms-popup-preview__headline" data-preview-headline @if ($previewHeadline === '') hidden @endif>{{ $previewHeadline }}</h2>
                        <p class="cms-popup-preview__subheadline" data-preview-subheadline @if ($previewSubheadline === '') hidden @endif>{{ $previewSubheadline }}</p>
                        <span class="cms-popup-preview__cta" data-preview-cta @if ($previewButtonText === '') hidden @endif>{{ $previewButtonText }}</span>
                    </div>
                </article>
            </div>
            <button type="button" class="cms-popup-preview__close" data-preview-close tabindex="-1" @if (! $previewClosable) hidden @endif>×</button>
            <label class="cms-popup-preview__optout" data-preview-optout @if (! $previewClosable) hidden @endif>
                <input type="checkbox" tabindex="-1" disabled>
                <span>{{ __('cms::admin.popup_preview_hide_seven_days') }}</span>
            </label>
        </div>
    </div>

    <p class="cms-popup-preview__meta" data-preview-meta>
        {{ __('cms::admin.popup_preview_delay', ['seconds' => $previewDelay]) }}
        @if (is_numeric($previewAutoClose) && (int) $previewAutoClose > 0)
            · {{ __('cms::admin.popup_preview_auto_close', ['seconds' => (int) $previewAutoClose]) }}
        @endif
    </p>
</aside>
