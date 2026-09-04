@props([
    'product' => null,
])

@php
    $customMeta = $product?->meta['custom'] ?? [];
    $customMetaJson = old(
        'meta.custom_json',
        $customMeta !== [] ? json_encode($customMeta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ''
    );
    $productEvents = collect(config('webhooks.events', []))
        ->filter(static fn (string $label, string $event): bool => str_starts_with($event, 'product.'))
        ->all();
@endphp

<section class="cf-product-workspace__section">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">Advanced</h2>
        <p class="cf-product-workspace__section-desc">Identifiers and power-user settings. Rarely needed for day-to-day catalog work.</p>
    </header>

    <div class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2">
        @if ($product)
            <div class="cf-product-workspace__field">
                <label class="cf-product-workspace__label">Product UUID</label>
                <input type="text" class="cf-input" value="{{ $product->uuid }}" readonly>
            </div>
        @endif

        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="external_id">External ID</label>
            <input
                id="external_id"
                name="meta[external_id]"
                type="text"
                class="cf-input"
                value="{{ old('meta.external_id', $product?->meta['external_id'] ?? '') }}"
                placeholder="ERP or marketplace reference"
            >
        </div>

        <div class="cf-product-workspace__field cf-product-workspace__field--full">
            <label class="cf-product-workspace__label" for="meta_notes">Internal notes</label>
            <textarea
                id="meta_notes"
                name="meta[notes]"
                rows="3"
                class="cf-input"
                placeholder="Visible to staff only"
            >{{ old('meta.notes', $product?->meta['notes'] ?? '') }}</textarea>
        </div>

        <div class="cf-product-workspace__field cf-product-workspace__field--full">
            <label class="cf-product-workspace__label" for="meta_custom_json">Custom meta JSON</label>
            <textarea
                id="meta_custom_json"
                name="meta[custom_json]"
                rows="6"
                class="cf-input font-mono text-sm"
                placeholder='{"erp_code":"ABC-123"}'
            >{{ $customMetaJson }}</textarea>
            <p class="cf-product-workspace__hint mt-1">Optional key/value data for integrations. Must be valid JSON.</p>
        </div>
    </div>

    @if ($product)
        <div class="cf-product-workspace__api-preview">
            <p class="cf-product-workspace__hint">API resource</p>
            <code class="cf-product-workspace__code">GET /api/v1/admin/products/{{ $product->uuid }}</code>
        </div>
    @endif

    @if ($productEvents !== [])
        <div class="cf-product-workspace__api-preview mt-4">
            <p class="cf-product-workspace__hint">Webhook events</p>
            <ul class="mt-2 space-y-1 text-sm text-text-secondary">
                @foreach ($productEvents as $event => $label)
                    <li><code class="cf-product-workspace__code">{{ $event }}</code> — {{ $label }}</li>
                @endforeach
            </ul>
            @if (Route::has('admin.webhooks.index'))
                <p class="mt-3 text-sm">
                    <a href="{{ route('admin.webhooks.index') }}" class="text-accent hover:underline">Manage webhooks</a>
                    to subscribe to product lifecycle events.
                </p>
            @endif
        </div>
    @endif
</section>
