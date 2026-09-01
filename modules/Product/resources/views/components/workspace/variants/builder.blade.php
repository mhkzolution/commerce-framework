@props([
    'skuPatterns' => [],
    'optionPresets' => [],
    'inventoryUrl' => null,
    'variants' => [],
    'options' => [],
])

@php
    $skuPatterns = $skuPatterns !== [] ? $skuPatterns : [
        '{PRODUCT}-{COLOR}-{SIZE}' => __('product::workspace.sku_pattern_product_color_size'),
        '{PRODUCT}-{INDEX}' => __('product::workspace.sku_pattern_product_index'),
        'random' => __('product::workspace.sku_pattern_random'),
    ];
@endphp

<section class="cf-variant-builder" data-variant-builder data-media-picker-url="{{ route('admin.media.picker') }}">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">{{ __('product::workspace.variants_title') }}</h2>
        <p class="cf-product-workspace__section-desc">
            {{ __('product::workspace.variants_desc') }}
        </p>
    </header>

    <div class="cf-variant-builder__steps">
        <x-product::workspace.variants.option-selector :presets="$optionPresets" :options="$options" />

        <x-product::workspace.variants.matrix-generator :sku-patterns="$skuPatterns" />
    </div>

    <x-product::workspace.variants.bulk-toolbar />

    <div class="cf-variant-builder__grid-wrap" data-variant-grid-desktop>
        <x-product::workspace.variants.grid :inventory-url="$inventoryUrl" :variants="$variants" />
    </div>

    <div class="cf-variant-builder__cards-wrap" data-variant-grid-mobile>
        <div class="cf-variant-cards" data-variant-cards></div>
    </div>

    <dialog class="cf-variant-image-dialog" data-variant-image-dialog>
        <div class="cf-variant-image-dialog__panel">
            <header class="cf-variant-image-dialog__header">
                <h3 class="cf-variant-image-dialog__title">{{ __('product::workspace.assign_variant_image') }}</h3>
                <button type="button" class="cf-variant-image-dialog__close" data-variant-image-close>&times;</button>
            </header>
            <div class="cf-variant-image-dialog__search">
                <input type="search" class="cf-input" placeholder="{{ __('product::workspace.search_images') }}" data-variant-image-search>
            </div>
            <div class="cf-variant-image-dialog__grid" data-variant-image-grid></div>
        </div>
    </dialog>
</section>
