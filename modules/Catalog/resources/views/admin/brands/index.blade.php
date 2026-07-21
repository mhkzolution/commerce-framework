@extends('layouts.admin')

@section('title', 'Brands')

@section('page')
    <x-admin.page title="Brands" description="Product brands">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Brands', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.catalog.brands.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New brand
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Logo</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($brands as $brand)
                <tr>
                    <td class="px-4 py-3">
                        @if (! empty($logoUrls[$brand->uuid]))
                            <img src="{{ $logoUrls[$brand->uuid] }}" alt="{{ $brand->name }}" class="h-10 w-10 rounded object-cover">
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium text-text">{{ $brand->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $brand->slug }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$brand->is_active ? 'published' : 'archived'">
                            {{ $brand->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.catalog.brands.edit', $brand)">Edit</x-admin.button>
                        <form method="POST" action="{{ route('admin.catalog.brands.destroy', $brand) }}" class="inline" onsubmit="return confirm('Delete this brand?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ml-2 text-sm text-danger hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No brands yet.</td></tr>
            @endforelse

            @if ($brands->hasPages())
                <x-slot:pagination>{{ $brands->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
