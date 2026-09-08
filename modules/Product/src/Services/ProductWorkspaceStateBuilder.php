<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;

final class ProductWorkspaceStateBuilder
{
    public function __construct(
        private readonly ?SettingQueryServiceInterface $settingQueryService = null,
        private readonly ?VariantOptionPresetService $variantOptionPresets = null,
    ) {}

    /**
     * @param  array<string, mixed>  $stockLevels
     * @return array<string, mixed>
     */
    public function build(?Product $product = null, array $stockLevels = []): array
    {
        $variants = [];
        $defaultVariant = $product?->variants->firstWhere('is_default', true) ?? $product?->variants->first();
        $defaultStock = $defaultVariant === null ? null : ($stockLevels[$defaultVariant->uuid] ?? null);

        if ($product !== null) {
            $product->loadMissing([
                'productAttributes.attribute',
                'attributeValues.attribute',
                'attributeValues.attributeValue',
                'variants',
            ]);

            foreach ($product->variants as $variant) {
                $stock = $stockLevels[$variant->uuid] ?? null;
                $meta = is_array($variant->meta) ? $variant->meta : [];
                $imageUuid = is_string($meta['image_media_uuid'] ?? null) ? $meta['image_media_uuid'] : null;

                $variants[] = [
                    'id' => $variant->uuid,
                    'uuid' => $variant->uuid,
                    'name' => $variant->name ?? $product->name,
                    'sku' => $variant->sku ?? '',
                    'price' => $this->majorFromMinor($variant->price),
                    'cost' => $meta['cost'] ?? '',
                    'comparePrice' => $this->majorFromMinor($variant->compare_at_price),
                    'weight' => $meta['weight'] ?? '',
                    'status' => $meta['status'] ?? 'active',
                    'trackInventory' => (bool) $variant->track_inventory,
                    'skuIsAuto' => (bool) $variant->sku_is_auto,
                    'imageMediaUuid' => $imageUuid,
                    'imagePreviewUrl' => $this->variantImagePreview($imageUuid),
                    'options' => $this->variantOptionsFromRelations($product, $variant),
                    'valueIds' => $this->variantValueIdsFromRelations($product, $variant),
                    'stock' => [
                        'onHand' => $stock?->getOnHand() ?? 0,
                        'reserved' => $stock?->getReserved() ?? 0,
                        'available' => $stock?->getAvailable() ?? 0,
                        'incoming' => 0,
                    ],
                    'isDefault' => (bool) $variant->is_default,
                ];
            }
        }

        $meta = is_array($product?->meta) ? $product->meta : [];

        return [
            'mode' => $product === null ? 'create' : 'edit',
            'inventoryBaseUrl' => url('/admin/inventory/purchasable'),
            'optionPresets' => $this->presets()->presetMap(),
            'labels' => [
                'allChangesSaved' => __('product::workspace.all_changes_saved'),
                'unsavedChanges' => __('product::workspace.unsaved_changes'),
                'discardConfirm' => __('product::workspace.discard_confirm'),
                'usedForVariations' => __('product::workspace.used_for_variations'),
                'usedForVariationsUncheckConfirm' => __('product::workspace.used_for_variations_uncheck_confirm'),
            ],
            'product' => [
                'name' => $product?->name ?? '',
                'slug' => $product?->slug ?? '',
                'description' => $product?->description ?? '',
                'brandUuid' => $product?->brand_uuid ?? '',
                'categoryIds' => $product?->categories->pluck('id')->all() ?? [],
                'collectionIds' => $product?->collections->pluck('id')->all() ?? [],
                'status' => $product?->status ?? 'draft',
                'visibility' => $product?->visibility ?? 'public',
                'publishAt' => $product?->publish_at?->format('Y-m-d\TH:i') ?? '',
                'sellerUuid' => $product?->seller_uuid ?? '',
                'attributeSetId' => $product?->attribute_set_id ?? '',
                'type' => $product?->type ?? 'simple',
                'backorderPolicy' => $product?->backorder_policy ?? 'deny',
                'trackInventory' => $defaultVariant === null ? true : (bool) $defaultVariant->track_inventory,
                'sku' => $defaultVariant?->sku ?? '',
                'skuPrefix' => is_string($meta['sku_prefix'] ?? null) ? $meta['sku_prefix'] : '',
                'price' => $this->majorFromMinor($defaultVariant?->price),
                'onHand' => $defaultStock?->getOnHand() ?? 0,
                'reserved' => $defaultStock?->getReserved() ?? 0,
                'available' => $defaultStock?->getAvailable() ?? 0,
            ],
            'media' => [
                'productUuids' => $product?->media->pluck('media_uuid')->all() ?? [],
            ],
            'options' => $this->optionAxesFromRelations($product),
            'productAttributes' => $this->productAttributesFromRelations($product),
            'variants' => $variants,
            'skuPattern' => $meta['sku_pattern'] ?? $this->defaultSkuPattern(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productAttributesFromRelations(?Product $product): array
    {
        if ($product === null) {
            return [];
        }

        $valuesByAttribute = $product->attributeValues
            ->whereNull('product_variant_id')
            ->groupBy('attribute_id');

        return $product->productAttributes->map(static function ($row) use ($valuesByAttribute): array {
            $valueIds = ($valuesByAttribute->get($row->attribute_id) ?? collect())
                ->pluck('attribute_value_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();

            return [
                'attributeId' => (int) $row->attribute_id,
                'usedForVariations' => (bool) $row->used_for_variations,
                'position' => (int) $row->position,
                'valueIds' => $valueIds,
                'name' => (string) ($row->attribute?->name ?? ''),
                'type' => (string) ($row->attribute?->type ?? 'select'),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, string>
     */
    private function variantOptionsFromRelations(Product $product, ProductVariant $variant): array
    {
        $options = [];
        foreach ($product->attributeValues as $row) {
            if ((int) $row->product_variant_id !== (int) $variant->id) {
                continue;
            }

            $name = $row->attribute?->name;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $options[$name] = (string) ($row->attributeValue?->label ?? $row->value ?? '');
        }

        return $options;
    }

    /**
     * @return list<int>
     */
    private function variantValueIdsFromRelations(Product $product, ProductVariant $variant): array
    {
        return $product->attributeValues
            ->filter(static function ($row) use ($variant): bool {
                return (int) $row->product_variant_id === (int) $variant->id
                    && $row->attribute_value_id !== null;
            })
            ->pluck('attribute_value_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function optionAxesFromRelations(?Product $product): array
    {
        if ($product === null) {
            return [];
        }

        $valuesByAttribute = $product->attributeValues
            ->whereNull('product_variant_id')
            ->groupBy('attribute_id');

        return $product->productAttributes
            ->where('used_for_variations', true)
            ->map(function ($row) use ($valuesByAttribute): array {
                $values = ($valuesByAttribute->get($row->attribute_id) ?? collect())
                    ->map(static fn ($value): string => (string) ($value->attributeValue?->label ?? $value->value ?? ''))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => (string) $row->attribute_id,
                    'name' => (string) ($row->attribute?->name ?? ''),
                    'values' => $values,
                ];
            })
            ->values()
            ->all();
    }

    private function defaultSkuPattern(): string
    {
        $settings = $this->settingQueryService ?? (
            app()->bound(SettingQueryServiceInterface::class)
                ? app(SettingQueryServiceInterface::class)
                : null
        );

        if ($settings === null) {
            return '{PRODUCT}-{COLOR}-{SIZE}';
        }

        $pattern = $settings->get('product.sku_pattern');

        return is_string($pattern) && $pattern !== '' ? $pattern : '{PRODUCT}-{COLOR}-{SIZE}';
    }

    /**
     * @return array<string, mixed>
     */
    public function stockLevelsFor(Product $product): array
    {
        if (! app()->bound(InventoryQueryServiceInterface::class)) {
            return [];
        }

        return app(InventoryQueryServiceInterface::class)->levelsForPurchasables(
            $product->variants->pluck('uuid')->all(),
        );
    }

    private function variantImagePreview(?string $mediaUuid): ?string
    {
        if ($mediaUuid === null || $mediaUuid === '' || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        $media = app(MediaQueryServiceInterface::class);

        return $media->getUrl($mediaUuid, 'thumbnail') ?? $media->getUrl($mediaUuid);
    }

    private function presets(): VariantOptionPresetService
    {
        return $this->variantOptionPresets ?? app(VariantOptionPresetService::class);
    }

    private function majorFromMinor(mixed $minor): string
    {
        if ($minor === null || $minor === '') {
            return '';
        }

        return (string) ((int) $minor / 100);
    }
}
