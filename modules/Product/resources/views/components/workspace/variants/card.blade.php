<article class="cf-variant-card" data-variant-card>
    <header class="cf-variant-card__header">
        <input type="checkbox" data-variant-select aria-label="Select variant">
        <h4 class="cf-variant-card__title" data-variant-card-title>Variant</h4>
        <button type="button" class="cf-variant-card__toggle" data-variant-card-toggle aria-expanded="false">
            <span class="sr-only">Toggle details</span>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </button>
    </header>

    <div class="cf-variant-card__summary">
        <x-product::workspace.variants.stock-summary />
    </div>

    <div class="cf-variant-card__body hidden" data-variant-card-body>
        <div class="cf-variant-card__fields">
            <label class="cf-variant-card__field">
                <span>SKU</span>
                <input type="text" class="cf-input" data-variant-field="sku">
            </label>
            <label class="cf-variant-card__field">
                <span>Price</span>
                <input type="number" class="cf-input" data-variant-field="price" step="0.01" min="0">
            </label>
            <label class="cf-variant-card__field">
                <span>Cost</span>
                <input type="number" class="cf-input" data-variant-field="cost" step="0.01" min="0">
            </label>
            <label class="cf-variant-card__field">
                <span>Compare at</span>
                <input type="number" class="cf-input" data-variant-field="comparePrice" step="0.01" min="0">
            </label>
            <label class="cf-variant-card__field">
                <span>Weight (g)</span>
                <input type="number" class="cf-input" data-variant-field="weight" step="0.01" min="0">
            </label>
            <label class="cf-variant-card__field">
                <span>Status</span>
                <select class="cf-input" data-variant-field="status">
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            </label>
        </div>

        <button type="button" class="cf-btn cf-btn--danger cf-btn--sm cf-variant-card__delete" data-variant-delete>
            Delete variant
        </button>
    </div>
</article>
