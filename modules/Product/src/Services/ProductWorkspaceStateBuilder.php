<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Product\Models\Product;

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

        if ($product !== null) {
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
                    'imageMediaUuid' => $imageUuid,
                    'imagePreviewUrl' => $this->variantImagePreview($imageUuid),
                    'options' => $meta['options'] ?? [],
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
            'options' => $meta['variant_options'] ?? [],
            'variants' => $variants,
            'skuPattern' => $meta['sku_pattern'] ?? $this->defaultSkuPattern(),
        ];
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
