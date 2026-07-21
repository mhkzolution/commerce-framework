@extends('layouts.admin')

@section('title', 'Products')

@section('page')
    <x-admin.page title="Products" description="Sellable products and variants.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.products.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New product
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search name, slug, SKU" />
                        </form>
                    </x-slot:search>
                    <x-slot:filters>
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <select name="status" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Image</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($products as $product)
                @php $variant = $product->defaultVariant(); @endphp
                <tr>
                    <td class="px-4 py-3">
                        @if (! empty($imageUrls[$product->uuid]))
                            <img src="{{ $imageUrls[$product->uuid] }}" alt="" class="h-10 w-10 rounded object-cover">
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $product->name }}</div>
                        <div class="text-xs text-muted">{{ $product->type }} · {{ $product->slug }}</div>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $variant?->sku ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $variant ? number_format($variant->price / 100, 2) : '—' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusVariant = match ($product->status) {
                                'published' => 'published',
                                'draft' => 'draft',
                                'scheduled' => 'pending',
                                'archived' => 'archived',
                                default => 'default',
                            };
                        @endphp
                        <x-admin.badge :variant="$statusVariant">
                            {{ $statuses[$product->status] ?? $product->status }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.products.edit', $product)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No products yet.</td></tr>
            @endforelse

            @if ($products->hasPages())
                <x-slot:pagination>{{ $products->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
