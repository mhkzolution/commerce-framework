@extends('layouts.admin')
@section('title', 'Commissions')
@section('page')
    <x-admin.page title="Commissions" :description="$seller->name">
        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('seller.dashboard')">Dashboard</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Line total</th><th class="px-4 py-3">Commission</th><th class="px-4 py-3">Status</th></tr></x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ $item->order_uuid }}</td>
                    <td class="px-4 py-3">{{ number_format($item->line_total / 100, 2) }}</td>
                    <td class="px-4 py-3">{{ number_format($item->commission_amount / 100, 2) }}</td>
                    <td class="px-4 py-3">{{ $item->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No commissions.</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
