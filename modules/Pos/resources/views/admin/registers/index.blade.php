@extends('layouts.admin')
@section('title', 'Registers')
@section('page')
    <x-admin.page title="Registers" description="Manage point-of-sale registers.">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.pos.registers.create')">New register</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->location ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="px-4 py-3 text-right space-x-2">
                        @if ($item->is_active)
                            <x-admin.button variant="link" :href="route('admin.pos.terminal.show', $item)">Open terminal</x-admin.button>
                        @endif
                        <x-admin.button variant="link" :href="route('admin.pos.registers.edit', $item)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No registers yet.</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
