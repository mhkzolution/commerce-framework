@extends('layouts.admin')

@section('title', 'Sellers')

@section('page')
    <x-admin.page title="Sellers" description="Manage marketplace vendors and commission rates.">
        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('admin.marketplace.commissions.index')">Commissions</x-admin.button>
            <x-admin.button variant="primary" :href="route('admin.marketplace.sellers.create')">New seller</x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3 text-right">Commission</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->email ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->commission_rate / 100, 2) }}%</td>
                    <td class="px-4 py-3">{{ ucfirst($item->status) }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.marketplace.sellers.edit', $item)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-muted">No sellers yet.</td>
                </tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
