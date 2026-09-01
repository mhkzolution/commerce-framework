@extends('layouts.admin')

@section('title', __('barcode::admin.title'))

@push('head')
    @vite(['resources/css/barcode.css', 'resources/js/barcode/index.js'])
@endpush

@section('page')
    @php
        $config = $workspaceConfig;
        $defaultTemplate = $config['default_template'];
    @endphp

    <div
        class="bc-workspace"
        id="bc-app"
        data-bc-app
        data-bc-config='@json($appConfig)'
    >
        <header class="bc-workspace__header">
            <div class="bc-workspace__title-wrap">
                <h1 class="bc-workspace__title">{{ __('barcode::admin.title') }}</h1>
                <p class="bc-workspace__desc">{{ __('barcode::admin.description') }}</p>
            </div>

            <nav class="bc-workspace__nav" aria-label="{{ __('barcode::admin.title') }}">
                <x-admin.button variant="primary" :href="route('admin.barcode.index')" class="bc-workspace__nav-item bc-workspace__nav-item--active">
                    {{ __('barcode::admin.nav.print') }}
                </x-admin.button>
                @can('barcode.template.manage')
                    <x-admin.button variant="ghost" :href="route('admin.barcode.templates.index')" class="bc-workspace__nav-item">
                        {{ __('barcode::admin.nav.templates') }}
                    </x-admin.button>
                @endcan
                @can('barcode.history.view')
                    <x-admin.button variant="ghost" :href="route('admin.barcode.history.index')" class="bc-workspace__nav-item">
                        {{ __('barcode::admin.nav.history') }}
                    </x-admin.button>
                @endcan
            </nav>
        </header>

        <div class="bc-workspace__panels">
            <div class="bc-workspace__panel bc-workspace__panel--search">
                <x-barcode::barcode-search
                    :search-url="route('admin.barcode.search')"
                    :sellers="$config['sellers'] ?? []"
                    :site-name="$appConfig['siteName'] ?? ''"
                />
            </div>

            <div class="bc-workspace__panel bc-workspace__panel--queue">
                <x-barcode::barcode-queue />
            </div>

            <div class="bc-workspace__panel bc-workspace__panel--preview">
                <x-barcode::template-selector
                    :templates="$config['templates']"
                    :selected-id="$defaultTemplate['id']"
                />
                <x-barcode::paper-settings
                    :paper-sizes="$config['paper_sizes']"
                    :settings="$defaultTemplate"
                />
                <x-barcode::barcode-preview />
            </div>
        </div>

        <div class="bc-mobile-bar" data-bc-mobile-bar hidden>
            <div class="bc-mobile-bar__summary">
                <span class="bc-mobile-bar__labels" data-bc-mobile-total-labels>0</span>
                <span class="bc-mobile-bar__label-text">{{ __('barcode::admin.queue.total_labels') }}</span>
            </div>
            <button type="button" class="cf-btn cf-btn--secondary" data-bc-open-queue>
                {{ __('barcode::admin.queue.title') }}
            </button>
            <button type="button" class="cf-btn cf-btn--primary" data-bc-print>
                {{ __('barcode::admin.preview.print') }}
            </button>
        </div>
    </div>
@endsection
