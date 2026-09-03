@php
    $item = $item ?? null;
    $type = old('type', $item?->type ?? 'image');
@endphp

<x-admin.form.section title="{{ __('cms::admin.hero_banner') }}">
    <div class="grid gap-6 sm:grid-cols-2" data-hero-banner-form>
        <div>
            <label class="block text-sm font-medium text-text" for="type">{{ __('cms::admin.hero_type') }}</label>
            <select id="type" name="type" class="cf-input mt-1" data-hero-type>
                <option value="image" @selected($type === 'image')>{{ __('cms::admin.hero_type_image') }}</option>
                <option value="video" @selected($type === 'video')>{{ __('cms::admin.hero_type_video') }}</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            @include('media::components.file-attach', [
                'name' => 'image_media_uuid',
                'value' => old('image_media_uuid', $item?->image_media_uuid),
                'label' => __('cms::admin.image'),
                'help' => __('cms::admin.hero_poster_helper'),
                'imagesOnly' => true,
                'layout' => 'banner',
            ])
        </div>
        <div class="sm:col-span-2">
            @include('media::components.file-attach', [
                'name' => 'mobile_image_media_uuid',
                'value' => old('mobile_image_media_uuid', $item?->mobile_image_media_uuid),
                'label' => __('cms::admin.mobile_image'),
                'help' => __('cms::admin.mobile_image_helper'),
                'imagesOnly' => true,
                'layout' => 'banner',
            ])
        </div>
        <div class="sm:col-span-2" data-hero-video-fields>
            @include('media::components.file-attach', [
                'name' => 'video_media_uuid',
                'value' => old('video_media_uuid', $item?->video_media_uuid),
                'label' => __('cms::admin.desktop_video'),
                'help' => __('cms::admin.desktop_video_helper'),
                'imagesOnly' => false,
                'accept' => 'video/mp4,video/webm,.mp4,.webm',
                'layout' => 'banner',
            ])
        </div>
        <div class="sm:col-span-2" data-hero-video-fields>
            @include('media::components.file-attach', [
                'name' => 'mobile_video_media_uuid',
                'value' => old('mobile_video_media_uuid', $item?->mobile_video_media_uuid),
                'label' => __('cms::admin.mobile_video'),
                'help' => __('cms::admin.mobile_video_helper'),
                'imagesOnly' => false,
                'accept' => 'video/mp4,video/webm,.mp4,.webm',
                'layout' => 'banner',
            ])
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="sort_order">{{ __('cms::admin.sort_order') }}</label>
            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $item?->sort_order ?? 0) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="starts_at">{{ __('cms::admin.starts_at') }}</label>
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-hero-banner-form]');
            if (!root) {
                return;
            }
            const type = root.querySelector('[data-hero-type]');
            const videoFields = root.querySelectorAll('[data-hero-video-fields]');
            const sync = () => {
                const isVideo = type.value === 'video';
                videoFields.forEach((field) => {
                    field.hidden = !isVideo;
                });
            };
            type.addEventListener('change', sync);
            sync();
        });
    </script>
@endpush
