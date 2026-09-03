@extends('layouts.admin')
@section('title', __('cms::admin.faq_entries'))
@section('page')
    <x-admin.page :title="__('cms::admin.faq_entries')" :description="__('cms::admin.faq_entries_description')">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.cms.faq-entries.create')">{{ __('cms::admin.create_faq_entry') }}</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">{{ __('cms::admin.question') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.sort_order') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('cms::admin.actions') }}</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->question }}</td>
                    <td class="px-4 py-3">{{ $item->sort_order }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$item->is_active ? 'published' : 'archived'">
                            {{ $item->is_active ? __('cms::admin.active') : __('cms::admin.inactive') }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('admin.cms.faq-entries.edit', $item)">{{ __('cms::admin.edit') }}</x-admin.button></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">{{ __('cms::admin.no_records') }}</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
