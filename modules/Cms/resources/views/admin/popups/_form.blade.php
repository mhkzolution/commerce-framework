@php
    $item = $item ?? null;
    $type = old('popup_type', $item?->popup_type ?? 'image');
    $status = old('status', $item?->status ?? 'draft');
    $target = old('button_target', $item?->button_target ?? 'self');
@endphp

<div class="cms-popup-workspace">
<div class="cms-popup-workspace__fields space-y-6">
<x-admin.form.section title="{{ __('cms::admin.popup') }}">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-text" for="title">{{ __('cms::admin.title_label') }}</label>
            <input id="title" name="title" value="{{ old('title', $item?->title) }}" required class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="slug">{{ __('cms::admin.slug') }}</label>
            <input id="slug" name="slug" value="{{ old('slug', $item?->slug) }}" class="cf-input mt-1" placeholder="{{ __('cms::admin.slug_placeholder') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="status">{{ __('cms::admin.status') }}</label>
            <select id="status" name="status" class="cf-input mt-1">
                <option value="draft" @selected($status === 'draft')>{{ __('cms::admin.status_draft') }}</option>
                <option value="published" @selected($status === 'published')>{{ __('cms::admin.status_published') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="popup_type">{{ __('cms::admin.popup_type') }}</label>
            <select id="popup_type" name="popup_type" class="cf-input mt-1">
                <option value="image" @selected($type === 'image')>{{ __('cms::admin.popup_type_image') }}</option>
                <option value="content" @selected($type === 'content')>{{ __('cms::admin.popup_type_content') }}</option>
                <option value="promotion" @selected($type === 'promotion')>{{ __('cms::admin.popup_type_promotion') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="priority">{{ __('cms::admin.priority') }}</label>
            <input id="priority" name="priority" type="number" min="0" value="{{ old('priority', $item?->priority ?? 0) }}" class="cf-input mt-1">
            <p class="mt-1 text-sm text-muted">{{ __('cms::admin.priority_hint') }}</p>
        </div>
        <div class="flex items-end gap-6">
            <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item?->is_active ?? true)) class="rounded border-border">
                {{ __('cms::admin.active') }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                <input type="hidden" name="closable" value="0">
                <input type="checkbox" name="closable" value="1" @checked(old('closable', $item?->closable ?? true)) class="rounded border-border">
                {{ __('cms::admin.closable') }}
            </label>
        </div>
    </div>
</x-admin.form.section>

<x-admin.form.section title="{{ __('cms::admin.popup_content') }}">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            @include('cms::admin.partials.media-attach', [
                'name' => 'image_media_uuid',
                'value' => old('image_media_uuid', $item?->image_media_uuid),
                'label' => __('cms::admin.image'),
            ])
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="headline">{{ __('cms::admin.headline') }}</label>
            <input id="headline" name="headline" value="{{ old('headline', $item?->headline) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="button_text">{{ __('cms::admin.button_text') }}</label>
            <input id="button_text" name="button_text" value="{{ old('button_text', $item?->button_text) }}" class="cf-input mt-1" placeholder="Shop Now">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-text" for="subheadline">{{ __('cms::admin.subheadline') }}</label>
            <textarea id="subheadline" name="subheadline" rows="3" class="cf-input mt-1">{{ old('subheadline', $item?->subheadline) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="button_url">{{ __('cms::admin.button_url') }}</label>
            <input id="button_url" name="button_url" value="{{ old('button_url', $item?->button_url) }}" class="cf-input mt-1" placeholder="/shop">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="button_target">{{ __('cms::admin.button_target') }}</label>
            <select id="button_target" name="button_target" class="cf-input mt-1">
                <option value="self" @selected($target === 'self')>{{ __('cms::admin.button_target_self') }}</option>
                <option value="blank" @selected($target === 'blank')>{{ __('cms::admin.button_target_blank') }}</option>
            </select>
        </div>
    </div>
</x-admin.form.section>

<x-admin.form.section title="{{ __('cms::admin.popup_timing') }}">
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-text" for="show_delay">{{ __('cms::admin.show_delay') }}</label>
            <input id="show_delay" name="show_delay" type="number" min="0" value="{{ old('show_delay', $item?->show_delay ?? 1) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="auto_close">{{ __('cms::admin.auto_close') }}</label>
            <input id="auto_close" name="auto_close" type="number" min="0" value="{{ old('auto_close', $item?->auto_close) }}" class="cf-input mt-1" placeholder="0">
            <p class="mt-1 text-sm text-muted">{{ __('cms::admin.auto_close_hint') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="start_at">{{ __('cms::admin.starts_at') }}</label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at', optional($item?->localStartAt())->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="end_at">{{ __('cms::admin.ends_at') }}</label>
            <input id="end_at" name="end_at" type="datetime-local" value="{{ old('end_at', optional($item?->localEndAt())->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="timezone">{{ __('cms::admin.timezone') }}</label>
            <input id="timezone" name="timezone" value="{{ old('timezone', $item?->timezone ?? 'Asia/Bangkok') }}" class="cf-input mt-1">
        </div>
    </div>
</x-admin.form.section>
</div>
@include('cms::admin.popups._preview')
</div>
