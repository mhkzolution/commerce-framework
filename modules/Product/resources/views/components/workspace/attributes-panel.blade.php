@props([
    'product' => null,
    'attributeSets' => collect(),
    'attributeSetsPayload' => [],
    'attributeValues' => collect(),
    'defaultAttributeSetId' => null,
])

@php
    $selectedAttributeSetId = (string) old(
        'attribute_set_id',
        $product?->attribute_set_id ?? $defaultAttributeSetId ?? '',
    );
@endphp

<section
    class="cf-product-workspace__section cf-attributes-panel"
    data-attributes-panel
    data-add-value-label="{{ __('product::workspace.add_attribute_value') }}"
>
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">{{ __('product::workspace.attributes_title') }}</h2>
        <p class="cf-product-workspace__section-desc">{{ __('product::workspace.attributes_desc') }}</p>
    </header>

    <div class="cf-product-workspace__field">
        <label class="cf-product-workspace__label" for="attribute_set_id">{{ __('product::workspace.attribute_set') }}</label>
        <select id="attribute_set_id" name="attribute_set_id" class="cf-input mt-1" data-attribute-set-select>
            <option value="">— None —</option>
            @foreach ($attributeSets as $set)
                <option value="{{ $set->id }}" @selected($selectedAttributeSetId === (string) $set->id)>{{ $set->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-muted">{{ __('product::workspace.attribute_set_hint') }}</p>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2" data-attribute-fields></div>
    <p
        class="mt-4 text-sm text-muted"
        data-attribute-empty
        data-set-empty="{{ __('product::workspace.attribute_set_empty') }}"
        data-has-attributes-empty="{{ __('product::workspace.attribute_set_no_attributes') }}"
    >{{ __('product::workspace.attribute_set_empty') }}</p>

    <label class="cf-attributes-panel__variation" data-used-for-variations hidden>
        <input type="checkbox" data-used-for-variations-input>
        <span>{{ __('product::workspace.used_for_variations') }}</span>
    </label>

    <div class="cf-attributes-panel__generate" data-generate-variants-wrap hidden>
        <button type="button" class="cf-btn cf-btn--primary" data-generate-variants disabled>
            {{ __('product::workspace.generate_variants') }}
        </button>
        <p class="cf-attributes-panel__generate-hint" data-generate-variants-hint>
            {{ __('product::workspace.generate_variants_hint') }}
        </p>
    </div>

    <script type="application/json" data-attribute-sets-catalog>
        @json($attributeSetsPayload ?? [])
    </script>
</section>
