@extends('layouts.admin')

@section('title', 'Edit Product')

@section('page')
    <x-admin.page
        :title="$product->name"
        :description="($product->slug).' · '.(config('product.types')[$product->type] ?? $product->type)"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => $product->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            @if ($product->status !== 'published')
                <form method="POST" action="{{ route('admin.products.publish', $product) }}">
                    @csrf
                    <x-admin.button variant="success" type="submit">Publish</x-admin.button>
                </form>
            @endif
            @if ($product->status !== 'archived')
                <form method="POST" action="{{ route('admin.products.archive', $product) }}">
                    @csrf
                    <x-admin.button variant="secondary" type="submit">Archive</x-admin.button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.products.update', $product) }}" method="POST" class="max-w-4xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Product details">
                @include('product::admin.products._form', ['product' => $product])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.products.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>

        @if (! empty($stockLevels))
            <x-admin.card title="Inventory" class="mt-6 max-w-4xl">
                <p class="mb-4 text-sm text-muted">Stock levels per variant</p>
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">Variant</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">On hand</th>
                            <th class="px-4 py-3">Reserved</th>
                            <th class="px-4 py-3">Available</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($product->variants as $variant)
                        @php $stock = $stockLevels[$variant->uuid] ?? null; @endphp
                        <tr>
                            <td class="px-4 py-3 text-text">
                                {{ $variant->name ?? '—' }}
                                @if ($variant->is_default)
                                    <span class="text-xs text-muted">(default)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $variant->sku ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $stock?->getOnHand() ?? 0 }}</td>
                            <td class="px-4 py-3">{{ $stock?->getReserved() ?? 0 }}</td>
                            <td class="px-4 py-3">{{ $stock?->getAvailable() ?? 0 }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-admin.button variant="link" :href="route('admin.inventory.purchasable', $variant->uuid)">Manage stock</x-admin.button>
                            </td>
                        </tr>
                    @endforeach
                </x-admin.table.shell>
            </x-admin.card>
        @endif

        @if ($product->type === 'variable')
            <x-admin.card title="Variants" class="mt-6 max-w-4xl">
                <x-admin.table.shell>
                    <x-slot:head>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Price</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </x-slot:head>

                    @foreach ($product->variants as $variant)
                        <tr>
                            <td class="px-4 py-3 text-text">
                                {{ $variant->name ?? '—' }}
                                @if ($variant->is_default)
                                    <span class="text-xs text-muted">(default)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $variant->sku ?? '—' }}</td>
                            <td class="px-4 py-3">{{ number_format($variant->price / 100, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($product->variants->count() > 1)
                                    <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" class="inline" onsubmit="return confirm('Delete variant?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-danger hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-admin.table.shell>

                <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                    @csrf
                    <input name="name" placeholder="Variant name" class="cf-input">
                    <input name="sku" placeholder="SKU" class="cf-input">
                    <input name="price" type="number" step="0.01" min="0" placeholder="Price" required class="cf-input">
                    <x-admin.button variant="secondary" type="submit">Add variant</x-admin.button>
                </form>
            </x-admin.card>
        @endif
    </x-admin.page>
@endsection
