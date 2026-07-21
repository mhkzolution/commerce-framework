@extends('layouts.admin')

@section('title', 'Commissions')

@section('page')
    <x-admin.page title="Commissions" description="Seller commissions recorded from confirmed orders.">
        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('admin.marketplace.sellers.index')">Sellers</x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Seller</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3 text-right">Line total</th>
                    <th class="px-4 py-3 text-right">Rate</th>
                    <th class="px-4 py-3 text-right">Commission</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Recorded</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $item->seller?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted">
                        @if (Route::has('admin.orders.show'))
                            <a href="{{ route('admin.orders.show', $item->order_uuid) }}" class="hover:underline">{{ Str::limit($item->order_uuid, 8, '') }}</a>
                        @else
                            {{ Str::limit($item->order_uuid, 8, '') }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->line_total / 100, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($item->commission_rate / 100, 2) }}%</td>
                    <td class="px-4 py-3 text-right font-medium text-text">{{ number_format($item->commission_amount / 100, 2) }}</td>
                    <td class="px-4 py-3">{{ ucfirst($item->status) }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->created_at?->format('M j, Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-muted">No commissions recorded yet.</td>
                </tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
