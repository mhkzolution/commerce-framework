@extends('layouts.admin')
@section('title', 'Payouts')
@section('page')
    <x-admin.page title="Payouts" description="Seller payout requests.">
        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession
        <x-admin.table.shell>
            <x-slot:head><tr><th class="px-4 py-3">Seller</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->seller?->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->amount / 100, 2) }}</td>
                    <td class="px-4 py-3">{{ $item->status }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($item->status === 'pending')
                            <form method="POST" action="{{ route('admin.marketplace.payouts.mark-paid', $item) }}" class="inline-flex items-center gap-2">
                                @csrf
                                <input name="reference" class="cf-input w-32 py-1 text-sm" placeholder="Reference">
                                <x-admin.button variant="secondary" type="submit">Mark paid</x-admin.button>
                            </form>
                        @else
                            <span class="text-sm text-muted">{{ $item->paid_at?->format('Y-m-d') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No payouts.</td></tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
