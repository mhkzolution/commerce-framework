@props([
    'name' => 'media_uuid',
    'value' => null,
    'label' => 'Media',
    'multiple' => false,
    'imagesOnly' => true,
])

@php
    $previewUrl = null;
    if ($value) {
        $previewUrl = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($value, 'thumbnail')
            ?? app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($value);
    }
    $pickerId = 'media-picker-' . md5($name . ($value ?? ''));
@endphp

<div
    id="{{ $pickerId }}"
    class="space-y-3"
    data-picker-root
    data-picker-url="{{ route('admin.media.picker') }}"
    data-picker-multiple="{{ $multiple ? '1' : '0' }}"
    data-picker-images="{{ $imagesOnly ? '1' : '0' }}"
>
    <label class="block text-sm font-medium text-text">{{ $label }}</label>

    <input type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}" data-picker-input>

    <div class="flex items-start gap-4">
        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-md border border-border bg-surface-muted" data-picker-preview>
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" alt="Selected media" class="h-full w-full object-cover">
            @else
                <span class="text-xs text-muted">{{ __('media::admin.no_image') }}</span>
            @endif
        </div>

        <div class="space-y-2">
            <button type="button" class="cf-btn cf-btn-primary" data-picker-open>
                {{ __('media::admin.choose_from_library') }}
            </button>
            <button type="button" class="block text-sm text-muted hover:text-text" data-picker-clear>
                {{ __('media::admin.clear') }}
            </button>
        </div>
    </div>
</div>
