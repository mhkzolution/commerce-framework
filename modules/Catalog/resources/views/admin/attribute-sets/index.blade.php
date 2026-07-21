@extends('layouts.admin')

@section('title', 'Attribute Sets')

@section('page')
    <x-admin.page title="Attribute Sets" description="Group attributes for product types">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Attribute Sets', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.catalog.attribute-sets.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New set
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Attributes</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($attributeSets as $set)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ $set->code }}</td>
                    <td class="px-4 py-3 font-medium text-text">{{ $set->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $set->attributes_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.catalog.attribute-sets.edit', $set)">Edit</x-admin.button>
                        <form method="POST" action="{{ route('admin.catalog.attribute-sets.destroy', $set) }}" class="inline" onsubmit="return confirm('Delete this attribute set?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ml-2 text-sm text-danger hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No attribute sets yet.</td></tr>
            @endforelse

            @if ($attributeSets->hasPages())
                <x-slot:pagination>{{ $attributeSets->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
