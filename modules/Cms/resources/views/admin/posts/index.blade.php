@extends('layouts.admin')
@section('title', 'Posts')
@section('page')
    <x-admin.page title="Posts" description="Manage blog posts.">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.cms.posts.create')">New post</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">
                        {{ $item->title }}
                        @if ($item->is_featured)
                            <span class="ml-2 text-xs text-muted">Featured</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->category?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $item->status }}</td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('admin.cms.posts.edit', $item)">Edit</x-admin.button></td>
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
