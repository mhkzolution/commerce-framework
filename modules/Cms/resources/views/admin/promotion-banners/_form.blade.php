@php
    $item = $item ?? null;
@endphp

<x-admin.form.section title="{{ __('cms::admin.promotion_banner') }}">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-text" for="title">{{ __('cms::admin.title_label') }}</label>
            <input id="title" name="title" value="{{ old('title', $item?->title) }}" required class="cf-input mt-1">
        </div>
        <div class="sm:col-span-2">
            @include('media::components.file-attach', [
                'name' => 'image_media_uuid',
                'value' => old('image_media_uuid', $item?->image_media_uuid),
                'label' => __('cms::admin.image'),
                'imagesOnly' => true,
                'layout' => 'banner',
            ])
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="url">{{ __('cms::admin.link_url') }}</label>
            <input id="url" name="url" value="{{ old('url', $item?->url) }}" class="cf-input mt-1" placeholder="/shop or https://">
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                <input type="hidden" name="open_in_new_tab" value="0">
                <input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $item?->open_in_new_tab)) class="rounded border-border">
                {{ __('cms::admin.open_in_new_tab') }}
            </label>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="sort_order">{{ __('cms::admin.sort_order') }}</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $item?->sort_order ?? 0) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="starts_at">{{ __('cms::admin.schedule_publish') }}</label>
            <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', optional($item?->starts_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="ends_at">{{ __('cms::admin.ends_at') }}</label>
            <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', optional($item?->ends_at)->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active ?? true)) class="rounded border-border">
                {{ __('cms::admin.active') }}
            </label>
        </div>
    </div>
</x-admin.form.section>
