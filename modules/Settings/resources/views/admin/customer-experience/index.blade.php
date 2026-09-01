@extends('layouts.admin')

@section('title', __('settings::admin.customer_experience_title'))

@php
    $sections = [
        'quickView' => __('settings::admin.cx_section_quickView'),
        'notifications' => __('settings::admin.cx_section_notifications'),
        'navigation' => __('settings::admin.cx_section_navigation'),
        'productCard' => __('settings::admin.cx_section_productCard'),
        'productDetail' => __('settings::admin.cx_section_productDetail'),
        'cart' => __('settings::admin.cx_section_cart'),
        'checkout' => __('settings::admin.cx_section_checkout'),
    ];

    $i18n = [
        'inStock' => __('settings::admin.cx_in_stock'),
        'left' => __('settings::admin.cx_left'),
        'viewFullDetail' => __('settings::admin.cx_view_full_detail'),
        'addToCart' => __('settings::admin.cx_add_to_cart'),
        'buyNow' => __('settings::admin.cx_buy_now'),
        'placeholder' => __('settings::admin.cx_placeholder_section'),
        'comingSoon' => __('settings::admin.cx_coming_soon'),
    ];

    $quickViewFields = [
        'showImages' => __('settings::admin.cx_show_images'),
        'showName' => __('settings::admin.cx_show_name'),
        'showPrice' => __('settings::admin.cx_show_price'),
        'showSalePrice' => __('settings::admin.cx_show_sale_price'),
        'showPromotionBadge' => __('settings::admin.cx_show_promotion_badge'),
        'showShortDescription' => __('settings::admin.cx_show_short_description'),
        'showFullDescription' => __('settings::admin.cx_show_full_description'),
        'showStockStatus' => __('settings::admin.cx_show_stock_status'),
        'showRemainingStock' => __('settings::admin.cx_show_remaining_stock'),
        'showSku' => __('settings::admin.cx_show_sku'),
        'showBrand' => __('settings::admin.cx_show_brand'),
        'showCategory' => __('settings::admin.cx_show_category'),
        'showTags' => __('settings::admin.cx_show_tags'),
        'showVariants' => __('settings::admin.cx_show_variants'),
        'showQuantitySelector' => __('settings::admin.cx_show_quantity'),
        'showAddToCart' => __('settings::admin.cx_show_add_to_cart'),
        'showBuyNow' => __('settings::admin.cx_show_buy_now'),
        'showWishlist' => __('settings::admin.cx_show_wishlist'),
        'showViewFullDetail' => __('settings::admin.cx_show_full_detail'),
    ];

    $notificationEvents = [
        'newProduct' => __('settings::admin.cx_event_new_product'),
        'promotion' => __('settings::admin.cx_event_promotion'),
        'lowStock' => __('settings::admin.cx_event_low_stock'),
        'recentPurchase' => __('settings::admin.cx_event_recent_purchase'),
        'review' => __('settings::admin.cx_event_review'),
    ];
@endphp

@section('page')
    <x-admin.page
        :title="__('settings::admin.customer_experience_title')"
        :description="__('settings::admin.customer_experience_description')"
        wide
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.website')],
                ['label' => __('settings::admin.customer_experience'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form
            method="POST"
            action="{{ route('admin.settings.customer-experience.update') }}"
            class="cx-settings"
            data-cx-settings
            data-cx-config='@json($config)'
            data-cx-preview='@json($preview)'
            data-cx-i18n='@json($i18n)'
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="config" value="{{ e(json_encode($config, JSON_UNESCAPED_UNICODE)) }}" data-cx-config-input>

            <div class="cx-settings__tabs" role="tablist">
                @foreach ($sections as $key => $label)
                    <button
                        type="button"
                        class="cx-settings__tab{{ $loop->first ? ' is-active' : '' }}"
                        data-cx-section-tab="{{ $key }}"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <p class="cx-settings__hint">{{ __('settings::admin.customer_experience_live_hint') }}</p>

            <div class="cx-settings__split">
                <section class="cx-settings__config" aria-label="{{ __('settings::admin.customer_experience_config') }}">
                    <div data-cx-panel="quickView">
                        <label class="cx-toggle">
                            <input type="checkbox" data-cx-path="quickView.enabled" @checked($config['quickView']['enabled'])>
                            <span>{{ __('settings::admin.cx_enable_quick_view') }}</span>
                        </label>

                        <h3 class="cx-settings__group-title">{{ __('settings::admin.cx_displayed_information') }}</h3>
                        <div class="cx-settings__checks">
                            @foreach ($quickViewFields as $path => $label)
                                <label class="cx-check">
                                    <input type="checkbox" data-cx-path="quickView.{{ $path }}" @checked($config['quickView'][$path] ?? false)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div data-cx-panel="notifications" hidden>
                        <label class="cx-toggle">
                            <input type="checkbox" data-cx-path="notifications.enabled" @checked($config['notifications']['enabled'])>
                            <span>{{ __('settings::admin.cx_enable_notifications') }}</span>
                        </label>

                        <div class="cx-settings__field">
                            <span class="cx-settings__label">{{ __('settings::admin.cx_duration') }}</span>
                            <div class="cx-settings__pills">
                                @foreach ([5, 10, 15] as $seconds)
                                    <label class="cx-pill">
                                        <input type="radio" name="cx_duration" data-cx-path="notifications.duration" data-cx-type="integer" value="{{ $seconds }}" @checked((int) $config['notifications']['duration'] === $seconds)>
                                        <span>{{ __('settings::admin.cx_seconds', ['count' => $seconds]) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="cx-settings__field">
                            <span class="cx-settings__label">{{ __('settings::admin.cx_position') }}</span>
                            <select class="cf-input" data-cx-path="notifications.position">
                                @foreach (['top-left', 'top-right', 'bottom-left', 'bottom-right'] as $position)
                                    <option value="{{ $position }}" @selected($config['notifications']['position'] === $position)>
                                        {{ __('settings::admin.cx_position_'.str_replace('-', '_', $position)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <h3 class="cx-settings__group-title">{{ __('settings::admin.cx_event_selection') }}</h3>
                        <div class="cx-settings__checks">
                            @foreach ($notificationEvents as $path => $label)
                                <label class="cx-check">
                                    <input type="checkbox" data-cx-path="notifications.{{ $path }}" data-cx-event="{{ $path }}" @checked($config['notifications'][$path] ?? false)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>

                        <button type="button" class="cf-btn cf-btn--primary mt-4" data-cx-simulate>
                            {{ __('settings::admin.cx_preview_notification') }}
                        </button>
                    </div>

                    <div data-cx-panel="navigation" hidden>
                        <label class="cx-toggle">
                            <input type="checkbox" data-cx-path="navigation.backToTop" @checked($config['navigation']['backToTop'])>
                            <span>{{ __('settings::admin.cx_enable_back_to_top') }}</span>
                        </label>

                        <div class="cx-settings__field">
                            <label class="cx-settings__label" for="cx-show-after">{{ __('settings::admin.cx_show_after') }}</label>
                            <input id="cx-show-after" class="cf-input" type="number" min="0" max="5000" step="50" data-cx-path="navigation.showAfter" data-cx-type="integer" value="{{ $config['navigation']['showAfter'] }}">
                        </div>

                        <div class="cx-settings__field">
                            <span class="cx-settings__label">{{ __('settings::admin.cx_position') }}</span>
                            <select class="cf-input" data-cx-path="navigation.position">
                                <option value="bottom-right" @selected($config['navigation']['position'] === 'bottom-right')>{{ __('settings::admin.cx_position_bottom_right') }}</option>
                                <option value="bottom-left" @selected($config['navigation']['position'] === 'bottom-left')>{{ __('settings::admin.cx_position_bottom_left') }}</option>
                            </select>
                        </div>

                        <div class="cx-settings__field">
                            <span class="cx-settings__label">{{ __('settings::admin.cx_style') }}</span>
                            <div class="cx-settings__pills">
                                @foreach (['circle', 'square', 'pill'] as $style)
                                    <label class="cx-pill">
                                        <input type="radio" name="cx_style" data-cx-path="navigation.style" value="{{ $style }}" @checked($config['navigation']['style'] === $style)>
                                        <span>{{ __('settings::admin.cx_style_'.$style) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <h3 class="cx-settings__group-title">{{ __('settings::admin.cx_animation') }}</h3>
                        <label class="cx-check">
                            <input type="checkbox" data-cx-path="navigation.fadeIn" @checked($config['navigation']['fadeIn'])>
                            <span>{{ __('settings::admin.cx_fade_in') }}</span>
                        </label>
                        <label class="cx-check">
                            <input type="checkbox" data-cx-path="navigation.smoothScroll" @checked($config['navigation']['smoothScroll'])>
                            <span>{{ __('settings::admin.cx_smooth_scroll') }}</span>
                        </label>

                        <div class="cx-settings__field">
                            <span class="cx-settings__label">{{ __('settings::admin.cx_future_target') }}</span>
                            <select class="cf-input" data-cx-path="navigation.target">
                                <option value="top" @selected(($config['navigation']['target'] ?? 'top') === 'top')>{{ __('settings::admin.cx_target_top') }}</option>
                                <option value="filter" @selected(($config['navigation']['target'] ?? '') === 'filter')>{{ __('settings::admin.cx_target_filter') }}</option>
                                <option value="category" @selected(($config['navigation']['target'] ?? '') === 'category')>{{ __('settings::admin.cx_target_category') }}</option>
                            </select>
                        </div>
                    </div>

                    @foreach (['productCard', 'productDetail', 'cart', 'checkout'] as $section)
                        <div data-cx-panel="{{ $section }}" hidden>
                            <label class="cx-toggle">
                                <input type="checkbox" data-cx-path="{{ $section }}.enabled" @checked($config[$section]['enabled'] ?? true)>
                                <span>{{ $sections[$section] }}</span>
                            </label>
                            <p class="cx-settings__coming-soon">{{ __('settings::admin.cx_coming_soon') }}</p>
                        </div>
                    @endforeach
                </section>

                <section class="cx-settings__preview" aria-label="{{ __('settings::admin.customer_experience_preview') }}">
                    <div class="cx-settings__device-toggle">
                        <button type="button" class="cx-settings__device is-active" data-cx-device="desktop">{{ __('settings::admin.customer_experience_device_desktop') }}</button>
                        <button type="button" class="cx-settings__device" data-cx-device="mobile">{{ __('settings::admin.customer_experience_device_mobile') }}</button>
                    </div>

                    <div class="cx-device" data-cx-device-frame data-device="desktop">
                        <div class="cx-device__chrome">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="cx-device__screen" data-cx-preview-root></div>
                    </div>
                </section>
            </div>

            <div class="cx-settings__actions">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.customer_experience_save') }}</x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
