@props([
    'product' => null,
    'tags' => collect(),
    'sellers' => collect(),
    'attributeSets' => collect(),
    'attributeSetsPayload' => [],
    'attributeOptionPresets' => [],
    'attributeValues' => collect(),
    'defaultAttributeSetId' => null,
])

<section class="cf-product-workspace__section">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">Organization</h2>
        <p class="cf-product-workspace__section-desc">Internal classification, marketplace seller, and tags.</p>
    </header>

    <div class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2">
        <div class="cf-product-workspace__field cf-product-workspace__field--full">
            <label class="cf-product-workspace__label">Tags</label>
            @include('product::admin.products._form-tags', ['product' => $product, 'showLabel' => false])
        </div>

        @if ($sellers->isNotEmpty())
            <div class="cf-product-workspace__field">
                <label class="cf-product-workspace__label" for="seller_uuid">Seller</label>
                <select id="seller_uuid" name="seller_uuid" class="cf-input">
                    <option value="">— None —</option>
                    @foreach ($sellers as $seller)
                        <option value="{{ $seller->uuid }}" @selected(old('seller_uuid', $product?->seller_uuid) === $seller->uuid)>
                            {{ $seller->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    @include('product::admin.products._attributes', [
        'product' => $product,
        'attributeSets' => $attributeSets,
        'attributeSetsPayload' => $attributeSetsPayload,
        'attributeOptionPresets' => $attributeOptionPresets,
        'attributeValues' => $attributeValues,
        'defaultAttributeSetId' => $defaultAttributeSetId,
    ])
</section>
