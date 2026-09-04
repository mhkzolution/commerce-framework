@extends('layouts.admin')

@section('title', 'Products')

@section('page')
    @php
        $listingFilters = array_filter([
            'search' => request('search'),
            'status' => request('status'),
        ]);
        $showBulk = $canDelete && $products->isNotEmpty();
    @endphp

    <x-admin.page title="Products" description="Sellable products and variants.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('admin.products.export', request()->only(['search', 'status']))">
                <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
                Export CSV
            </x-admin.button>
            @if ($canImport)
                <x-admin.button variant="secondary" :href="route('admin.products.import.show')">
                    <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
                    Import CSV
                </x-admin.button>
            @endif
            <x-admin.button variant="primary" :href="route('admin.products.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New product
            </x-admin.button>
        </x-slot:primaryActions>

        @if ($showBulk)
            <form
                method="POST"
                action="{{ route('admin.products.bulk-destroy') }}"
                id="product-bulk-form"
                class="hidden"
                aria-hidden="true"
            >
                @csrf
                @if (! empty($listingFilters['search']))
                    <input type="hidden" name="search" value="{{ $listingFilters['search'] }}">
                @endif
                @if (! empty($listingFilters['status']))
                    <input type="hidden" name="status" value="{{ $listingFilters['status'] }}">
                @endif
            </form>
        @endif

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

            @if ($showBulk)
                <x-slot:bulk>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-muted">
                            <span data-product-bulk-count>0</span> selected
                        </p>
                        <x-admin.button form="product-bulk-form" type="submit" variant="danger" data-product-bulk-submit disabled>
                            Delete selected
                        </x-admin.button>
                    </div>
                </x-slot:bulk>
            @endif

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    @if ($showBulk)
                        <th class="w-10 px-4 py-3">
                            <label class="sr-only" for="product-select-all">Select all products</label>
                            <input
                                id="product-select-all"
                                type="checkbox"
                                class="rounded border-border"
                                data-product-select-all
                            >
                        </th>
                    @endif
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
                    @if ($showBulk)
                        <td class="px-4 py-3">
                            <label class="sr-only" for="product-select-{{ $product->uuid }}">Select {{ $product->name }}</label>
                            <input
                                id="product-select-{{ $product->uuid }}"
                                type="checkbox"
                                name="uuids[]"
                                value="{{ $product->uuid }}"
                                form="product-bulk-form"
                                class="rounded border-border"
                                data-product-bulk-uuid
                            >
                        </td>
                    @endif
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
                <tr>
                        <td colspan="{{ $showBulk ? 7 : 6 }}" class="px-4 py-8 text-center text-muted">No products yet.</td>
                </tr>
            @endforelse

            @if ($products->hasPages())
                <x-slot:pagination>{{ $products->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>

    @if ($showBulk)
        <script>
            (() => {
                const form = document.getElementById('product-bulk-form');
                const selectAll = document.querySelector('[data-product-select-all]');
                const countEl = document.querySelector('[data-product-bulk-count]');
                const submitBtn = document.querySelector('[data-product-bulk-submit]');
                const boxes = () => Array.from(document.querySelectorAll('[data-product-bulk-uuid]'));

                const sync = () => {
                    const selected = boxes().filter((box) => box.checked);
                    if (countEl) {
                        countEl.textContent = String(selected.length);
                    }
                    if (submitBtn) {
                        submitBtn.disabled = selected.length === 0;
                    }
                    if (selectAll) {
                        selectAll.checked = boxes().length > 0 && selected.length === boxes().length;
                        selectAll.indeterminate = selected.length > 0 && selected.length < boxes().length;
                    }
                };

                selectAll?.addEventListener('change', () => {
                    boxes().forEach((box) => {
                        box.checked = selectAll.checked;
                    });
                    sync();
                });

                document.addEventListener('change', (event) => {
                    if (event.target?.matches?.('[data-product-bulk-uuid]')) {
                        sync();
                    }
                });

                form?.addEventListener('submit', (event) => {
                    const selected = boxes().filter((box) => box.checked).length;
                    if (selected === 0) {
                        event.preventDefault();
                        return;
                    }
                    const label = selected === 1 ? '1 product' : `${selected} products`;
                    if (!confirm(`Delete ${label}? This cannot be undone.`)) {
                        event.preventDefault();
                    }
                });

                sync();
            })();
        </script>
    @endif
@endsection
