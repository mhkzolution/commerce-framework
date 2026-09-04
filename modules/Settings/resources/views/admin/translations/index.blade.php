@extends('layouts.admin')

@section('title', __('settings::admin.translations_title'))

@section('page')
    <x-admin.page
        :title="__('settings::admin.translations_title')"
        :description="__('settings::admin.translations_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('settings::admin.configuration')],
                ['label' => __('settings::admin.translations'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            @foreach ($locales as $code => $label)
                <a
                    href="{{ route('admin.settings.translations.index', ['locale' => $code]) }}"
                    @class([
                        'cf-btn',
                        $locale === $code ? 'cf-btn-primary' : 'cf-btn-secondary',
                    ])
                >{{ $label }} ({{ $code }})</a>
            @endforeach
        </div>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('settings::admin.translations_file') }}</th>
                    <th class="px-4 py-3">{{ __('settings::admin.translations_locale') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('settings::admin.edit') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($files as $entry)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm text-text">{{ $entry['label'] }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ strtoupper($entry['locale']) }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button
                            variant="ghost"
                            size="sm"
                            :href="route('admin.settings.translations.edit', ['namespace' => $entry['namespace'], 'file' => $entry['file'], 'locale' => $entry['locale']])"
                        >
                            {{ __('settings::admin.edit') }}
                        </x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-muted">
                        {{ __('settings::admin.translations_empty') }}
                    </td>
                </tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
