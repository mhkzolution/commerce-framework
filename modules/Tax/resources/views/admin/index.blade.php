@extends('layouts.admin')

@section('title', 'Tax rates')

@section('page')
    <x-admin.page title="Tax rates" description="Tax rules applied at checkout.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Tax rates', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.tax.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                Add rate
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Rate</th>
                    <th class="px-4 py-3">Country</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($rates as $rate)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $rate->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $rate->code }}</td>
                    <td class="px-4 py-3">{{ number_format($rate->rate_bps / 100, 2) }}%</td>
                    <td class="px-4 py-3">{{ $rate->country_code ?? 'All' }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.tax.edit', $rate)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No tax rates yet.</td></tr>
            @endforelse

            @if ($rates->hasPages())
                <x-slot:pagination>{{ $rates->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
