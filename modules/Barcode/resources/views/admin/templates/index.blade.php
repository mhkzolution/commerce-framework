@extends('layouts.admin')

@section('title', __('barcode::admin.templates.title'))

@section('page')
    <x-admin.page :title="__('barcode::admin.templates.title')" :description="__('barcode::admin.templates.description')">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => __('barcode::admin.title'), 'href' => route('admin.barcode.index')],
                ['label' => __('barcode::admin.templates.title'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.barcode.templates.create')">
                {{ __('barcode::admin.templates.new') }}
            </x-admin.button>
        </x-slot:primaryActions>

        <x-slot:secondaryActions>
            <x-admin.button variant="ghost" :href="route('admin.barcode.index')">
                {{ __('barcode::admin.nav.print') }}
            </x-admin.button>
        </x-slot:secondaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">{{ __('barcode::admin.templates.name') }}</th>
                    <th class="px-4 py-3">{{ __('barcode::admin.templates.paper_size') }}</th>
                    <th class="px-4 py-3">{{ __('barcode::admin.templates.grid') }}</th>
                    <th class="px-4 py-3">{{ __('barcode::admin.templates.label_size') }}</th>
                    <th class="px-4 py-3">{{ __('barcode::admin.templates.favorite') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('barcode::admin.templates.actions') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($templates as $template)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $template->name }}</div>
                        @if ($template->is_default)
                            <x-admin.badge variant="published" class="mt-1">{{ __('barcode::admin.templates.default') }}</x-admin.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ config("barcode.paper_sizes.{$template->paper_size}.label", $template->paper_size) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $template->columns }}×{{ $template->rows }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $template->label_width }} × {{ $template->label_height }} mm</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        <form method="POST" action="{{ route('admin.barcode.templates.favorite', $template) }}">
                            @csrf
                            <button type="submit" class="text-lg" title="{{ __('barcode::admin.templates.favorite') }}">
                                {{ $template->is_favorite ? '★' : '☆' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <x-admin.button variant="ghost" :href="route('admin.barcode.templates.edit', $template)">
                                {{ __('barcode::admin.templates.edit') }}
                            </x-admin.button>
                            <form method="POST" action="{{ route('admin.barcode.templates.duplicate', $template) }}">
                                @csrf
                                <x-admin.button variant="secondary" type="submit">{{ __('barcode::admin.templates.duplicate') }}</x-admin.button>
                            </form>
                            <form method="POST" action="{{ route('admin.barcode.templates.destroy', $template) }}" onsubmit="return confirm('{{ __('barcode::admin.templates.delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <x-admin.button variant="danger" type="submit">{{ __('barcode::admin.templates.delete') }}</x-admin.button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table.shell>
    </x-admin.page>
@endsection
