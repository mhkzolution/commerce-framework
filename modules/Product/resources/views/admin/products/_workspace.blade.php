@php
    $mode = $mode ?? ($product ? 'edit' : 'create');
    $pageTitle = $mode === 'create' ? __('product::workspace.new_product') : $product->name;
    $formAction = $mode === 'create'
        ? route('admin.products.store')
        : route('admin.products.update', $product);
    $formMethod = $mode === 'create' ? 'POST' : 'PUT';
@endphp

<x-product::workspace.workspace
    :action="$formAction"
    :method="$formMethod"
    :product="$product"
    :mode="$mode"
    :initial-state="$initialState ?? []"
>
    <x-slot:header>
        <x-product::workspace.header
            :product="$product"
            :mode="$mode"
            :statuses="$statuses ?? []"
        >
            <x-slot:actions>
                @if ($mode === 'edit' && $product)
                    @php
                        $workspaceDomId = 'product-workspace-' . $product->uuid;
                    @endphp
                    @if ($product->status !== 'published')
                        <x-admin.button
                            variant="success"
                            type="submit"
                            :form="$workspaceDomId . '-publish'"
                        >
                            {{ __('product::workspace.publish') }}
                        </x-admin.button>
                    @endif
                    @if ($product->status !== 'archived')
                        <x-admin.button
                            variant="secondary"
                            type="submit"
                            :form="$workspaceDomId . '-archive'"
                        >
                            {{ __('product::workspace.archive') }}
                        </x-admin.button>
                    @endif
                @endif
            </x-slot:actions>
        </x-product::workspace.header>
    </x-slot:header>

    <x-slot:tabs>
        <x-product::workspace.tabs>
            <x-slot:general>
                <x-product::workspace.general-form
                    :product="$product"
                    :brands="$brands ?? collect()"
                    :categories="$categories ?? collect()"
                    :collections="$collections ?? collect()"
                    :statuses="$statuses ?? []"
                    :visibilities="$visibilities ?? []"
                />
            </x-slot:general>

            <x-slot:media>
                <x-product::workspace.media-manager
                    :media-uuids="old('media_uuids', $product?->media->pluck('media_uuid')->all() ?? [])"
                    :media-previews="$mediaPreviews ?? []"
                    :media-types="$mediaTypes ?? []"
                />
            </x-slot:media>

            <x-slot:variants>
                <x-product::workspace.variants.builder
                    :variants="$initialState['variants'] ?? []"
                    :options="$initialState['options'] ?? []"
                    :option-presets="$initialState['optionPresets'] ?? []"
                    :inventory-url="url('/admin/inventory/purchasable')"
                />
            </x-slot:variants>

            <x-slot:seo>
                <x-product::workspace.seo-form :seo="$seo ?? null" />
            </x-slot:seo>

            <x-slot:organization>
                <x-product::workspace.organization-form
                    :product="$product"
                    :tags="$tags ?? collect()"
                    :sellers="$sellers ?? collect()"
                    :attribute-sets="$attributeSets ?? collect()"
                    :attribute-sets-payload="$attributeSetsPayload ?? []"
                    :attribute-option-presets="$attributeOptionPresets ?? []"
                    :attribute-values="$attributeValues ?? collect()"
                    :default-attribute-set-id="$defaultAttributeSetId ?? null"
                />
            </x-slot:organization>

            <x-slot:advanced>
                <x-product::workspace.advanced-form :product="$product" />
            </x-slot:advanced>
        </x-product::workspace.tabs>
    </x-slot:tabs>
</x-product::workspace.workspace>
