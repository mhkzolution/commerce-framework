@extends('layouts.admin')

@section('title', 'Inventory')

@section('page')
    <x-admin.page title="Inventory" description="Stock levels per product variant.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Inventory', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search product, SKU, variant UUID" />
                        </form>
                    </x-slot:search>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Variant / SKU</th>
                    <th class="px-4 py-3">On hand</th>
                    <th class="px-4 py-3">Reserved</th>
                    <th class="px-4 py-3">Available</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($items as $item)
                @php
                    $context = $variantContext[$item->purchasable_uuid] ?? ['variant' => null, 'product_name' => null];
                    $variant = $context['variant'];
                    $available = $item->available();
                    $isLow = $available > 0 && $available <= $lowStockThreshold;
                    $isOut = $available <= 0;
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $context['product_name'] ?? '—' }}</div>
                        <div class="text-xs text-muted">{{ Str::limit($item->purchasable_uuid, 18) }}</div>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $variant?->name ?? '—' }}
                        <div class="text-xs">{{ $variant?->sku ?? '—' }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $item->on_hand }}</td>
                    <td class="px-4 py-3">{{ $item->reserved }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$isOut ? 'danger' : ($isLow ? 'warning' : 'published')">
                            {{ $available }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.inventory.show', $item)">Manage</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-muted">
                        No stock records yet. Adjust stock from a product variant or receive inventory after creating items.
                    </td>
                </tr>
            @endforelse

            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
