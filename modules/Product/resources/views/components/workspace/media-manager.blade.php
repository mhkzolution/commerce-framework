@props([
    'mediaUuids' => [],
    'mediaPreviews' => [],
    'mediaTypes' => [],
])

<section class="cf-product-workspace__section">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">Media</h2>
        <p class="cf-product-workspace__section-desc">Images, videos, and documents. Drag to reorder. Assign to variants from the Variants tab.</p>
    </header>

    <div class="cf-product-media-manager" data-product-media-manager>
        <div class="cf-product-media-manager__toolbar">
            <div class="cf-product-media-manager__filters" role="tablist">
                <button type="button" class="cf-product-media-manager__filter is-active" data-media-filter="all">All</button>
                <button type="button" class="cf-product-media-manager__filter" data-media-filter="image">Images</button>
                <button type="button" class="cf-product-media-manager__filter" data-media-filter="video">Videos</button>
                <button type="button" class="cf-product-media-manager__filter" data-media-filter="document">Documents</button>
            </div>
        </div>

        <div class="cf-product-media-manager__grid" data-media-grid>
            @include('product::components.images-attach', [
                'mediaUuids' => $mediaUuids,
                'mediaPreviews' => $mediaPreviews,
                'mediaTypes' => $mediaTypes ?? [],
                'showLabel' => false,
                'help' => null,
                'imagesOnly' => false,
                'label' => 'Product media',
            ])
        </div>

        <p class="cf-product-workspace__hint">
            First image is the product cover. Variant-specific images can be set in the variant grid.
        </p>
    </div>
</section>
