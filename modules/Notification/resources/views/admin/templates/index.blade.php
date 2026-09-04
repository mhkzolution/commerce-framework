@extends('layouts.admin')

@section('title', __('notification::admin.templates_title'))

@section('page')
    <x-admin.page
        :title="__('notification::admin.templates_title')"
        :description="__('notification::admin.templates_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.configuration')],
                ['label' => __('notification::admin.templates_title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('notification::admin.code') }}</th>
                    <th class="px-4 py-3">{{ __('notification::admin.name') }}</th>
                    <th class="px-4 py-3">{{ __('notification::admin.channel') }}</th>
                    <th class="px-4 py-3">{{ __('notification::admin.status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('notification::admin.actions') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($templates as $template)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm text-text">{{ $template->code }}</td>
                    <td class="px-4 py-3 text-text">{{ $template->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $template->channel }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$template->is_active ? 'published' : 'archived'">
                            {{ $template->is_active ? __('notification::admin.active') : __('notification::admin.inactive') }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.notification.templates.edit', $template)">
                            {{ __('notification::admin.edit') }}
                        </x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-muted">{{ __('notification::admin.empty') }}</td>
                </tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
