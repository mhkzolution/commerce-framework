@props([
    'skuPatterns' => [],
])

@php
    $skuPatterns = $skuPatterns !== [] ? $skuPatterns : [
        '{PRODUCT}-{COLOR}-{SIZE}' => __('product::workspace.sku_pattern_product_color_size'),
        '{PRODUCT}-{INDEX}' => __('product::workspace.sku_pattern_product_index'),
        'random' => __('product::workspace.sku_pattern_random'),
    ];
@endphp

<div class="cf-variant-step" data-variant-step="matrix">
    <div class="cf-variant-step__header">
        <span class="cf-variant-step__number">3</span>
        <div>
            <h3 class="cf-variant-step__title">{{ __('product::workspace.generate_variants') }}</h3>
            <p class="cf-variant-step__desc">{{ __('product::workspace.generate_variants_desc') }}</p>
        </div>
    </div>

    <div class="cf-variant-matrix">
        <div class="cf-variant-matrix__summary">
            <span class="cf-variant-matrix__count" data-variant-matrix-count>{{ trans_choice('product::workspace.variant_count', 1, ['count' => 1]) }}</span>
            <span class="cf-variant-matrix__formula" data-variant-matrix-formula>{{ __('product::workspace.default_variant_only') }}</span>
        </div>

        <div class="cf-variant-matrix__controls">
            <div class="cf-variant-matrix__pattern">
                <label class="cf-product-workspace__label" for="sku_pattern">{{ __('product::workspace.sku_pattern') }}</label>
                <select id="sku_pattern" class="cf-input" data-variant-sku-pattern>
                    @foreach ($skuPatterns as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="button" class="cf-btn cf-btn--primary" data-variant-generate>
                {{ __('product::workspace.generate_matrix') }}
            </button>
        </div>
    </div>
</div>

<div class="cf-variant-step" data-variant-step="grid">
    <div class="cf-variant-step__header">
        <span class="cf-variant-step__number">4</span>
        <div>
            <h3 class="cf-variant-step__title">{{ __('product::workspace.variant_grid') }}</h3>
            <p class="cf-variant-step__desc">{{ __('product::workspace.variant_grid_desc') }}</p>
        </div>
    </div>
</div>
