@props([
    'inventoryUrl' => null,
    'variants' => [],
])

<div class="cf-variant-grid" data-variant-grid>
    <div class="cf-variant-grid__scroll">
        <table class="cf-variant-grid__table">
            <thead>
                <tr>
                    <th class="cf-variant-grid__th cf-variant-grid__th--check">
                        <input type="checkbox" aria-label="Select all variants" data-variant-select-all>
                    </th>
                    <th class="cf-variant-grid__th">Image</th>
                    <th class="cf-variant-grid__th">Variant</th>
                    <th class="cf-variant-grid__th">SKU</th>
                    <th class="cf-variant-grid__th">Price</th>
                    <th class="cf-variant-grid__th">Cost</th>
                    <th class="cf-variant-grid__th">Compare</th>
                    <th class="cf-variant-grid__th">Weight</th>
                    <th class="cf-variant-grid__th">Stock</th>
                    <th class="cf-variant-grid__th">Status</th>
                    <th class="cf-variant-grid__th cf-variant-grid__th--actions">Actions</th>
                </tr>
            </thead>
            <tbody data-variant-grid-body>
                @foreach ($variants as $variant)
                    <x-product::workspace.variants.grid-row :variant="$variant" :inventory-url="$inventoryUrl" />
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<template id="variant-grid-row-template">
    <x-product::workspace.variants.grid-row :inventory-url="$inventoryUrl" />
</template>
