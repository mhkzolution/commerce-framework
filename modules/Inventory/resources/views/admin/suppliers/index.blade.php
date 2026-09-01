@extends('layouts.admin')

@section('title', 'Suppliers')

@section('page')
    <x-admin.page title="Suppliers" description="Vendors that fulfill purchase orders">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Suppliers', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-admin.button variant="primary" :href="route('admin.inventory.suppliers.create')">Add supplier</x-admin.button>
        </x-slot:actions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($suppliers as $supplier)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $supplier->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $supplier->email ?: '—' }}</td>
                    <td class="px-4 py-3 text-muted">{{ $supplier->phone ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-3">
                            <a href="{{ route('admin.inventory.suppliers.show', $supplier) }}" class="text-sm text-accent hover:underline">View</a>
                            <a href="{{ route('admin.inventory.suppliers.edit', $supplier) }}" class="text-sm text-accent hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.inventory.suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('Delete this supplier?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-danger hover:underline">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No suppliers yet.</td></tr>
            @endforelse

            @if ($suppliers->hasPages())
                <x-slot:pagination>{{ $suppliers->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
