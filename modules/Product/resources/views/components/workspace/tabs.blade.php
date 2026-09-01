@props([
    'defaultTab' => 'general',
])

@php
    $tabItems = [
        'general' => __('product::workspace.tab_general'),
        'media' => __('product::workspace.tab_media'),
        'variants' => __('product::workspace.tab_variants'),
        'seo' => __('product::workspace.tab_seo'),
        'organization' => __('product::workspace.tab_organization'),
        'advanced' => __('product::workspace.tab_advanced'),
    ];
@endphp

<div class="cf-product-workspace__tabs" data-product-workspace-tabs>
    <nav class="cf-product-workspace__tablist" role="tablist" aria-label="{{ __('product::workspace.tab_general') }}">
        @foreach ($tabItems as $key => $label)
            <button
                type="button"
                role="tab"
                class="cf-product-workspace__tab {{ $key === $defaultTab ? 'is-active' : '' }}"
                data-workspace-tab="{{ $key }}"
                aria-selected="{{ $key === $defaultTab ? 'true' : 'false' }}"
                aria-controls="workspace-panel-{{ $key }}"
            >
                {{ $label }}
                <span class="cf-product-workspace__tab-dot hidden" data-workspace-tab-dirty="{{ $key }}" aria-hidden="true"></span>
            </button>
        @endforeach
    </nav>

    <div class="cf-product-workspace__panels">
        @foreach ($tabItems as $key => $label)
            <section
                id="workspace-panel-{{ $key }}"
                role="tabpanel"
                class="cf-product-workspace__panel {{ $key === $defaultTab ? '' : 'hidden' }}"
                data-workspace-panel="{{ $key }}"
                @unless($key === $defaultTab) hidden @endunless
            >
                <div class="cf-product-workspace__panel-inner">
                    @switch($key)
                        @case('general')
                            {{ $general ?? '' }}
                            @break
                        @case('media')
                            {{ $media ?? '' }}
                            @break
                        @case('variants')
                            {{ $variants ?? '' }}
                            @break
                        @case('seo')
                            {{ $seo ?? '' }}
                            @break
                        @case('organization')
                            {{ $organization ?? '' }}
                            @break
                        @case('advanced')
                            {{ $advanced ?? '' }}
                            @break
                    @endswitch
                </div>
            </section>
        @endforeach
    </div>
</div>
