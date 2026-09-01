@props([
    'inventoryUrl' => null,
])

<button
    type="button"
    class="cf-variant-stock-summary"
    data-variant-stock-link
    title="Manage stock in Inventory"
>
    <span class="cf-variant-stock-summary__available" data-variant-stock-available>0</span>
    <span class="cf-variant-stock-summary__meta">
        <span data-variant-stock-on-hand>0 on hand</span>
        ·
        <span data-variant-stock-reserved>0 reserved</span>
        ·
        <span data-variant-stock-incoming>0 incoming</span>
    </span>
</button>
