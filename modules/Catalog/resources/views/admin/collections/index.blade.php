@extends('layouts.admin')

@section('title', 'Collections')

@section('page')
    <x-admin.page title="Collections" description="Curated product groups for campaigns and storefront sections">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Collections', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.card title="Add collection">
            <form method="POST" action="{{ route('admin.catalog.collections.store') }}" class="grid max-w-3xl gap-3">
                @csrf
                @include('catalog::admin.collections._form')
                <div>
                    <x-admin.button variant="primary" type="submit">Add collection</x-admin.button>
                </div>
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

            @forelse ($collections as $collection)
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            @if (! empty($coverUrls[$collection->uuid]))
                                <img src="{{ $coverUrls[$collection->uuid] }}" alt="" class="h-10 w-10 rounded object-cover">
                            @endif
                            <div>
                                <div class="font-medium text-text">{{ $collection->name }}</div>
                                @if ($collection->description)
                                    <div class="text-sm text-muted">{{ Str::limit($collection->description, 80) }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $collection->slug }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.catalog.collections.edit', $collection) }}" class="text-sm text-text hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.catalog.collections.destroy', $collection) }}" class="inline" onsubmit="return confirm('Delete this collection?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-danger hover:underline">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No collections yet.</td></tr>
            @endforelse

            @if ($collections->hasPages())
                <x-slot:pagination>{{ $collections->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
