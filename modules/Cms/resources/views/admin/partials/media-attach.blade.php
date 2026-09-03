@php
    $name = $name ?? 'image_media_uuid';
    $value = $value ?? null;
    $label = $label ?? __('cms::admin.image');
    $help = $help ?? null;
    $layout = $layout ?? 'banner';
@endphp

<div data-file-attach data-layout="{{ $layout }}" data-attach-dropzone>
    @include('media::components.media-picker', [
        'name' => $name,
        'value' => $value,
        'label' => $label,
    ])
    @if (filled($help))
        <p class="mt-1 text-sm text-muted">{{ $help }}</p>
    @endif
</div>
