@extends('layouts.admin')

@section('title', 'Currencies')

@section('page')
    <x-admin.page
        title="Currencies"
        :description="$baseCurrency ? 'Base currency: ' . $baseCurrency->code . ' — product prices are stored in this currency.' : 'Store currencies and exchange rates.'"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Currencies', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.currencies.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                Add currency
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search currencies..." />
                        </form>
                    </x-slot:search>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Rate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($currencies as $currency)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">
                        {{ $currency->code }}
                        @if ($currency->is_base)
                            <x-admin.badge variant="info" class="ml-2">Base</x-admin.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $currency->symbol }} {{ $currency->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ \Commerce\Currency\Support\CurrencyFormData::rateFromMicro($currency->rate_micro) }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$currency->is_active ? 'published' : 'archived'">
                            {{ $currency->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.currencies.edit', $currency)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No currencies yet.</td></tr>
            @endforelse

            @if ($currencies->hasPages())
                <x-slot:pagination>{{ $currencies->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
