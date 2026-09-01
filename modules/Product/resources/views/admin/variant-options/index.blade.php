@extends('layouts.admin')

@section('title', __('product::workspace.variant_options_title'))

@section('page')
    <x-admin.page
        :title="__('product::workspace.variant_options_title')"
        :description="__('product::workspace.variant_options_description')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('catalog::admin.catalog'), 'url' => route('admin.catalog.index')],
                ['label' => __('product::workspace.variant_options_title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.catalog.variant-options.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                {{ __('product::workspace.variant_options_create') }}
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('product::workspace.variant_option_name') }}</th>
                    <th class="px-4 py-3">{{ __('product::workspace.variant_option_code') }}</th>
                    <th class="px-4 py-3">{{ __('product::workspace.variant_option_values') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('product::workspace.actions') }}</th>
                </tr>
            </x-slot:head>

            @forelse ($options as $option)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $option->name }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ $option->code }}</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ implode(', ', array_slice($option->options ?? [], 0, 8)) }}
                        @if (count($option->options ?? []) > 8)
                            …
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="ghost" size="sm" :href="route('admin.catalog.variant-options.edit', $option)">
                            {{ __('product::workspace.edit') }}
                        </x-admin.button>
                        <form method="POST" action="{{ route('admin.catalog.variant-options.destroy', $option) }}" class="inline" onsubmit="return confirm(@js(__('product::workspace.variant_options_delete_confirm')))">
                            @csrf
                            @method('DELETE')
                            <x-admin.button variant="danger" size="sm" type="submit">{{ __('product::workspace.delete') }}</x-admin.button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-muted">
                        {{ __('product::workspace.variant_options_empty') }}
                    </td>
                </tr>
            @endforelse
        </x-admin.table.shell>

        @if ($options->hasPages())
            <div class="mt-4">{{ $options->links() }}</div>
        @endif
    </x-admin.page>
@endsection
