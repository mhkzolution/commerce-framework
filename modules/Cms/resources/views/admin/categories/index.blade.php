@extends('layouts.admin')
@section('title', 'Post categories')
@section('page')
    <x-admin.page title="Post categories" description="Organize blog posts.">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.cms.categories.create')">New category</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->name }}</td>
                    <td class="px-4 py-3">{{ $item->slug }}</td>
                    <td class="px-4 py-3">{{ $item->is_active ? 'Active' : 'Hidden' }}</td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('admin.cms.categories.edit', $item)">Edit</x-admin.button></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No records.</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
