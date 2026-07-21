@extends('layouts.admin')

@section('title', 'Tags')

@section('page')
    <x-admin.page title="Tags" description="Flexible labels for products">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Tags', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.card title="Add tag">
            <form method="POST" action="{{ route('admin.catalog.tags.store') }}" class="flex max-w-xl gap-3">
                @csrf
                <input name="name" placeholder="Tag name" required class="cf-input flex-1">
                <x-admin.button variant="primary" type="submit">Add tag</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.table.shell class="mt-6">
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($tags as $tag)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $tag->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $tag->slug }}</td>
                    <td class="px-4 py-3 text-right">
                        <form method="POST" action="{{ route('admin.catalog.tags.destroy', $tag) }}" class="inline" onsubmit="return confirm('Delete this tag?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-danger hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No tags yet.</td></tr>
            @endforelse

            @if ($tags->hasPages())
                <x-slot:pagination>{{ $tags->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
