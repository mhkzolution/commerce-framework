@props([
    'searchUrl' => '',
    'sellers' => [],
    'siteName' => '',
])

<div class="bc-search-panel">
    <div class="bc-search-mode" role="tablist" aria-label="{{ __('barcode::admin.search.mode_label') }}">
        <button
            type="button"
            class="bc-search-mode__tab bc-search-mode__tab--active"
            role="tab"
            aria-selected="true"
            data-bc-mode-products
        >
            {{ __('barcode::admin.search.mode_products') }}
        </button>
        <button
            type="button"
            class="bc-search-mode__tab"
            role="tab"
            aria-selected="false"
            data-bc-mode-manual
        >
            {{ __('barcode::admin.search.mode_manual') }}
        </button>
    </div>

<section class="bc-search" data-bc-search-section aria-label="{{ __('barcode::admin.search.title') }}">
    <div class="bc-search__header">
        <h2 class="bc-panel__title">{{ __('barcode::admin.search.title') }}</h2>
    </div>

    <div class="bc-search__input-wrap">
        <label for="bc-search-input" class="sr-only">{{ __('barcode::admin.search.placeholder') }}</label>
        <x-admin.search-input
            id="bc-search-input"
            name="q"
            :placeholder="__('barcode::admin.search.placeholder')"
            :value="null"
            data-bc-search-input
            autocomplete="off"
        />
        <p class="bc-search__hint">
            {{ __('barcode::admin.search.scanner_hint', ['shortcut' => __('barcode::admin.search.scanner_shortcut')]) }}
        </p>
    </div>

    <div class="bc-search__results" data-bc-search-results data-search-url="{{ $searchUrl }}" hidden>
        <p class="bc-search__empty" data-bc-search-empty hidden>{{ __('barcode::admin.search.no_results') }}</p>
        <ul class="bc-search__list" role="list" data-bc-search-list></ul>
    </div>

    <template id="bc-search-result-template">
        <li class="bc-search__item" data-bc-search-item>
            <div class="bc-search__item-thumb" data-bc-item-thumb aria-hidden="true"></div>
            <div class="bc-search__item-body">
                <p class="bc-search__item-owner" data-bc-item-owner></p>
                <p class="bc-search__item-name" data-bc-item-name></p>
                <p class="bc-search__item-sku" data-bc-item-sku></p>
            </div>
            <div class="bc-search__item-actions">
                <div class="bc-qty-stepper bc-qty-stepper--compact" data-bc-add-qty-wrap>
                    <button type="button" class="bc-qty-stepper__btn" data-bc-add-qty-decrease aria-label="{{ __('barcode::admin.queue.decrease') }}">−</button>
                    <input type="number" class="bc-qty-stepper__input" value="1" min="1" data-bc-add-qty-input aria-label="{{ __('barcode::admin.search.quantity') }}">
                    <button type="button" class="bc-qty-stepper__btn" data-bc-add-qty-increase aria-label="{{ __('barcode::admin.queue.increase') }}">+</button>
                </div>
                <button type="button" class="cf-btn cf-btn--primary cf-btn--sm" data-bc-add-to-queue>
                    {{ __('barcode::admin.search.add') }}
                </button>
            </div>
        </li>
    </template>
</section>

<x-barcode::barcode-manual
    :sellers="$sellers"
    :site-name="$siteName"
/>
</div>
