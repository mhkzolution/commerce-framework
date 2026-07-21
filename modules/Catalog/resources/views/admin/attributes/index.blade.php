@extends('layouts.admin')

@section('title', 'Attributes')

@section('page')
    <x-admin.page title="Attributes" description="Product property definitions">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Attributes', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.catalog.attributes.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New attribute
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($attributes as $attribute)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ $attribute->code }}</td>
                    <td class="px-4 py-3 font-medium text-text">{{ $attribute->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $types[$attribute->type] ?? $attribute->type }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.catalog.attributes.edit', $attribute)">Edit</x-admin.button>
                        <form method="POST" action="{{ route('admin.catalog.attributes.destroy', $attribute) }}" class="inline" onsubmit="return confirm('Delete this attribute?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ml-2 text-sm text-danger hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No attributes yet.</td></tr>
            @endforelse

            @if ($attributes->hasPages())
                <x-slot:pagination>{{ $attributes->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
