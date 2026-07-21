<section class="rounded-lg border border-border bg-primary-subtle/40 p-4">
    <h3 class="text-sm font-medium text-text">SEO</h3>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-text" for="seo-meta-title">Meta title</label>
            <input id="seo-meta-title" name="seo[meta_title]" value="{{ old('seo.meta_title', $seo?->meta_title) }}" class="cf-input mt-1">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-text" for="seo-meta-description">Meta description</label>
            <textarea id="seo-meta-description" name="seo[meta_description]" rows="3" class="cf-input mt-1">{{ old('seo.meta_description', $seo?->meta_description) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="seo-meta-keywords">Meta keywords</label>
            <input id="seo-meta-keywords" name="seo[meta_keywords]" value="{{ old('seo.meta_keywords', $seo?->meta_keywords) }}" class="cf-input mt-1">
        </div>
        <div>
            <label class="block text-sm font-medium text-text" for="seo-canonical-url">Canonical URL</label>
            <input id="seo-canonical-url" name="seo[canonical_url]" value="{{ old('seo.canonical_url', $seo?->canonical_url) }}" class="cf-input mt-1">
        </div>
    </div>

    @include('media::components.media-picker', [
        'name' => 'seo[og_image_media_uuid]',
        'value' => old('seo.og_image_media_uuid', $seo?->og_image_media_uuid),
        'label' => 'Open Graph image',
    ])
</section>
