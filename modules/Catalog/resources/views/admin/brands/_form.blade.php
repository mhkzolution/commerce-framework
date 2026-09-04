@php
    $brand = $brand ?? null;
@endphp

<div>
    <label class="block text-sm font-medium text-text" for="name">Name</label>
    <input id="name" name="name" value="{{ old('name', $brand?->name) }}" required class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="slug">Slug</label>
    <input id="slug" name="slug" value="{{ old('slug', $brand?->slug) }}" class="cf-input mt-1">
</div>
<div>
    <label class="block text-sm font-medium text-text" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="cf-input mt-1">{{ old('description', $brand?->description) }}</textarea>
</div>

@include('media::components.media-picker', [
    'name' => 'logo_media_uuid',
    'value' => old('logo_media_uuid', $brand?->logo_media_uuid),
    'label' => 'Brand logo',
])

<div>
    <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand?->is_active ?? true)) class="rounded border-border">
        Active
    </label>
</div>

@include('catalog::admin.partials.seo-fields', ['seo' => $seo ?? null])
