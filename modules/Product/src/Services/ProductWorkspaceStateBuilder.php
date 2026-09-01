<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Inventory\Services\IncomingStockQueryService;
use Commerce\Product\Models\Product;

final class ProductWorkspaceStateBuilder
{
    public function __construct(
        private readonly ?SettingQueryServiceInterface $settingQueryService = null,
        private readonly ?IncomingStockQueryService $incomingStockQuery = null,
        private readonly ?VariantOptionPresetService $variantOptionPresets = null,
    ) {}

    /**
     * @param  array<string, mixed>  $stockLevels
     * @return array<string, mixed>
     */
    public function build(?Product $product = null, array $stockLevels = []): array
    {
        $variants = [];

        if ($product !== null) {
            $incomingStock = $this->incomingLevelsFor($product);

            foreach ($product->variants as $variant) {
                $stock = $stockLevels[$variant->uuid] ?? null;

                $variants[] = [
                    'id' => $variant->uuid,
                    'uuid' => $variant->uuid,
                    'name' => $variant->name ?? $product->name,
                    'sku' => $variant->sku ?? '',
                    'price' => $variant->price !== null ? (string) $variant->price : '',
                    'cost' => $variant->cost !== null ? (string) $variant->cost : ($variant->meta['cost'] ?? ''),
                    'comparePrice' => $variant->compare_at_price !== null ? (string) $variant->compare_at_price : '',
                    'weight' => $variant->weight !== null ? (string) $variant->weight : ($variant->meta['weight'] ?? ''),
                    'status' => $variant->status ?? ($variant->meta['status'] ?? 'active'),
                    'imageMediaUuid' => $variant->meta['image_media_uuid'] ?? null,
                    'imagePreviewUrl' => $this->variantImagePreview($variant->meta['image_media_uuid'] ?? null),
                    'options' => $variant->meta['options'] ?? [],
                    'stock' => [
                        'onHand' => $stock?->getOnHand() ?? 0,
                        'reserved' => $stock?->getReserved() ?? 0,
                        'available' => $stock?->getAvailable() ?? 0,
                        'incoming' => $incomingStock[$variant->uuid] ?? 0,
                    ],
                    'isDefault' => (bool) $variant->is_default,
                ];
            }
        }

        return [
            'mode' => $product === null ? 'create' : 'edit',
            'inventoryBaseUrl' => url('/admin/inventory/purchasable'),
            'optionPresets' => $this->presets()->presetMap(),
            'labels' => [
                'allChangesSaved' => __('product::workspace.all_changes_saved'),
                'unsavedChanges' => __('product::workspace.unsaved_changes'),
                'discardConfirm' => __('product::workspace.discard_confirm'),
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
            ],
            'media' => [
                'productUuids' => $product?->media->pluck('media_uuid')->all() ?? [],
            ],
            'options' => $product?->meta['variant_options'] ?? [],
            'variants' => $variants,
            'skuPattern' => $product?->meta['sku_pattern'] ?? $this->defaultSkuPattern(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function incomingLevelsFor(Product $product): array
    {
        $service = $this->incomingStockQuery ?? (
            app()->bound(IncomingStockQueryService::class)
                ? app(IncomingStockQueryService::class)
                : null
        );

        if ($service === null) {
            return [];
        }

        return $service->incomingForPurchasables($product->variants->pluck('uuid')->all());
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
}
