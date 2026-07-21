@extends('layouts.admin')
@section('title', 'Page')
@section('page')
    <x-admin.page title="Page" description="Manage pages.">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.cms.pages.create')">New</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->title ?? $item->name ?? $item->uuid }}</td>
                    <td class="px-4 py-3">{{ $item->status ?? '—' }}</td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('admin.cms.pages.edit', $item)">Edit</x-admin.button></td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No records.</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection