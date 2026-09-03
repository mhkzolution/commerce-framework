@extends('layouts.admin')

@section('title', __('navigation::admin.title'))

@section('page')
    <x-admin.page :title="__('navigation::admin.title')" :description="__('navigation::admin.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('admin::nav.groups.website')],
                ['label' => __('navigation::admin.title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">{{ __('navigation::admin.handle') }}</th>
                    <th class="px-4 py-3">{{ __('navigation::admin.name') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('navigation::admin.edit') }}</th>
                </tr>
            </x-slot:head>
            @forelse ($menus as $menu)
                <tr>
                    <td class="px-4 py-3 font-mono text-sm">{{ $menu->handle }}</td>
                    <td class="px-4 py-3">{{ $menu->name }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.navigation.menus.edit', $menu)">
                            {{ __('navigation::admin.edit') }}
                        </x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-muted">{{ __('navigation::admin.empty') }}</td>
                </tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
