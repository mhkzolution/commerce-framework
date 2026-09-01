@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.nav_brands'))

@section('content')
    <div class="storefront-brands">
        <header class="storefront-brands__header">
            <x-storefront.breadcrumb :items="[
                ['label' => __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
                ['label' => __('storefront::storefront.nav_brands')],
            ]" />

            <div class="storefront-brands__title-row">
                <h1 class="storefront-brands__title">{{ __('storefront::storefront.nav_brands') }}</h1>
                @if ($brands->isNotEmpty())
                    <p class="storefront-brands__description">{{ __('storefront::storefront.brands_page_description') }}</p>
                @endif
            </div>
        </header>

        @if ($brands->isEmpty())
            <x-storefront.empty-state
                :title="__('storefront::storefront.brands_empty')"
                :description="__('storefront::storefront.brands_empty_description')"
            >
                <x-storefront.buttons.primary-button :href="route('storefront.shop.index')">
                    {{ __('storefront::storefront.continue_shopping') }}
                </x-storefront.buttons.primary-button>
            </x-storefront.empty-state>
        @else
            <x-storefront.layout.grid variant="brand" class="storefront-brands__grid">
                @foreach ($brands as $brand)
                    <x-storefront.cards.brand-card
                        :name="$brand->name"
                        :url="route('storefront.catalog.brands.show', $brand->slug)"
                        :logo-url="$brandLogoUrls[$brand->slug] ?? null"
                    />
                @endforeach
            </x-storefront.layout.grid>
        @endif
    </div>
@endsection
