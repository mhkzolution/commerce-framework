@extends('layouts.admin')
@section('title', __('cms::admin.popups'))
@section('page')
    <x-admin.page :title="__('cms::admin.popups')" :description="__('cms::admin.popups_description')">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.cms.popups.create')">{{ __('cms::admin.create_popup') }}</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">{{ __('cms::admin.thumbnail') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.title_label') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.popup_type') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.status') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.priority') }}</th>
                    <th class="px-4 py-3">{{ __('cms::admin.schedule') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('cms::admin.actions') }}</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">
                        @include('cms::admin.partials.media-thumb', [
                            'url' => $thumbnails[$item->uuid] ?? null,
                            'alt' => $item->title,
                        ])
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-text">{{ $item->title }}</p>
                        <p class="text-sm text-muted">{{ $item->slug }}</p>
                    </td>
                    <td class="px-4 py-3">{{ __('cms::admin.popup_type_'.$item->popup_type) }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$item->status === 'published' && $item->is_active ? 'published' : 'archived'">
                            {{ $item->is_active ? __('cms::admin.status_'.$item->status) : __('cms::admin.inactive') }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3">{{ $item->priority }}</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        @if ($item->start_at || $item->end_at)
                            {{ optional($item->localStartAt())->format('Y-m-d H:i') ?? '—' }}
                            →
                            {{ optional($item->localEndAt())->format('Y-m-d H:i') ?? '—' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('admin.cms.popups.edit', $item)">{{ __('cms::admin.edit') }}</x-admin.button></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-muted">{{ __('cms::admin.no_records') }}</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
