@extends('layouts.admin')
@section('title', 'Seller portal')
@section('page')
    <x-admin.page :title="$seller->name" description="Your marketplace earnings and payouts.">
        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        <div class="mb-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-border bg-surface p-4">
                <p class="text-sm text-muted">Available balance</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($availableBalance / 100, 2) }}</p>
            </div>
            <div class="rounded-lg border border-border bg-surface p-4 md:col-span-2 flex items-center">
                @if ($availableBalance > 0)
                    <form method="POST" action="{{ route('seller.payouts.request') }}">
                        @csrf
                        <x-admin.button variant="primary" type="submit">Request payout</x-admin.button>
                    </form>
                @else
                    <p class="text-sm text-muted">No pending commissions available for payout.</p>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section>
                <h2 class="mb-3 text-lg font-medium">Recent commissions</h2>
                <x-admin.table.shell>
                    <x-slot:head><tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th></tr></x-slot:head>
                    @forelse ($recentCommissions as $item)
                        <tr>
                            <td class="px-4 py-3 text-xs">{{ \Illuminate\Support\Str::limit($item->order_uuid, 12) }}</td>
                            <td class="px-4 py-3">{{ number_format($item->commission_amount / 100, 2) }}</td>
                            <td class="px-4 py-3">{{ $item->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No commissions yet.</td></tr>
                    @endforelse
                </x-admin.table.shell>
                <div class="mt-3">
                    <x-admin.button variant="link" :href="route('seller.commissions.index')">View all commissions</x-admin.button>
                </div>
            </section>

            <section>
                <h2 class="mb-3 text-lg font-medium">Recent payouts</h2>
                <x-admin.table.shell>
                    <x-slot:head><tr><th class="px-4 py-3">Amount</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Date</th></tr></x-slot:head>
                    @forelse ($recentPayouts as $item)
                        <tr>
                            <td class="px-4 py-3">{{ number_format($item->amount / 100, 2) }}</td>
                            <td class="px-4 py-3">{{ $item->status }}</td>
                            <td class="px-4 py-3">{{ $item->created_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No payouts yet.</td></tr>
                    @endforelse
                </x-admin.table.shell>
            </section>
        </div>
    </x-admin.page>
@endsection
