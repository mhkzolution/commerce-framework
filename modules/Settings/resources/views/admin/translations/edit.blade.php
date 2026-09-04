@extends('layouts.admin')

@section('title', __('settings::admin.translations_edit'))

@section('page')
    <x-admin.page :title="$label">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.translations'), 'url' => route('admin.settings.translations.index', ['locale' => $locale])],
                ['label' => $label, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <form method="GET" action="{{ route('admin.settings.translations.edit', ['namespace' => $namespace, 'file' => $file]) }}" class="mb-6 flex flex-wrap items-end gap-3">
            <input type="hidden" name="locale" value="{{ $locale }}">
            <div class="min-w-[16rem] flex-1">
                <label class="block text-sm font-medium text-text" for="search">{{ __('settings::admin.translations_search') }}</label>
                <input id="search" type="search" name="search" value="{{ $search }}" class="cf-input mt-1">
            </div>
            <x-admin.button variant="secondary" type="submit">{{ __('settings::admin.translations_search') }}</x-admin.button>
        </form>

        <form method="POST" action="{{ route('admin.settings.translations.update', ['namespace' => $namespace, 'file' => $file]) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="locale" value="{{ $locale }}">

            <x-admin.card :title="__('settings::admin.translations_keys') . ' (' . count($translations) . ')'">
                <div class="space-y-4">
                    @forelse ($translations as $key => $value)
                        <div class="grid gap-2 border-b border-border pb-4 last:border-b-0 last:pb-0 md:grid-cols-[minmax(12rem,1fr)_2fr]">
                            <label class="font-mono text-xs text-muted break-all" for="translation-{{ md5($key) }}">{{ $key }}</label>
                            <textarea
                                id="translation-{{ md5($key) }}"
                                name="translations[{{ $key }}]"
                                rows="2"
                                class="cf-input min-h-[2.5rem]"
                            >{{ old('translations.' . $key, $value) }}</textarea>
                        </div>
                    @empty
                        <p class="text-sm text-muted">{{ __('settings::admin.translations_empty') }}</p>
                    @endforelse
                </div>
            </x-admin.card>

            <div class="flex gap-2">
                <x-admin.button variant="primary" type="submit">{{ __('settings::admin.save') }}</x-admin.button>
                <x-admin.button variant="secondary" :href="route('admin.settings.translations.index', ['locale' => $locale])">
                    {{ __('settings::admin.cancel') }}
                </x-admin.button>
            </div>
        </form>
    </x-admin.page>
@endsection
