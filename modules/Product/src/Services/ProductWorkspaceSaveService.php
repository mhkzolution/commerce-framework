<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Attribute;
use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\DTO\SaveProductWorkspaceData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Events\ProductUnpublished;
use Commerce\Product\Events\ProductUpdated;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductWorkspaceSaveService
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly SeoServiceInterface $seoService,
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly ProductSearchIndexer $searchIndexer,
        private readonly ProductTypeChangeGuard $productTypeChangeGuard,
        private readonly InventoryServiceInterface $inventoryService,
        private readonly VariantMatrixGenerator $variantMatrixGenerator,
        private readonly VariableProductPublishGuard $publishGuard,
        private readonly ProductAttributeValueLinker $attributeValueLinker,
        private readonly ProductAttributeSetSync $productAttributeSetSync,
    ) {}

    public function create(SaveProductWorkspaceData $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = $this->persistProduct(null, $data);
            $this->eventBus->dispatch(new ProductCreated(
                productUuid: $product->uuid,
                type: $product->type,
            ));

            if ($product->isPublished()) {
                $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));
            }

            return $product;
        });
    }

    public function update(string $uuid, SaveProductWorkspaceData $data): Product
    {
        return DB::transaction(function () use ($uuid, $data): Product {
            $existing = $this->findOrFail($uuid);
            $wasPublished = $existing->isPublished();
            $oldSlug = $existing->slug;

            $product = $this->persistProduct($existing, $data);

            $this->eventBus->dispatch(new ProductUpdated(productUuid: $product->uuid));

            if (! $wasPublished && $product->isPublished()) {
                $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));
            }

            if ($wasPublished && ! $product->isPublished() && $product->status !== 'archived') {
                $this->eventBus->dispatch(new ProductUnpublished(productUuid: $product->uuid));
            }

            if ($oldSlug !== $product->slug) {
                $this->urlRedirectService->createRedirect(
                    $this->productPath($oldSlug),
                    $this->productPath($product->slug),
                );
            }

            return $product;
        });
    }

    private function persistProduct(?Product $existing, SaveProductWorkspaceData $data): Product
    {
        $existingType = $existing?->type;
        $variants = $data->variants;

        if ($variants === []) {
            $variants = [[
                'uuid' => $existing?->defaultVariant()?->uuid,
                'name' => $data->name,
                'sku' => $data->type === 'simple' ? $data->sku : null,
                'price' => $data->price,
                'onHand' => $data->onHand,
                'options' => [],
                'isDefault' => true,
            ]];
        }

        if ($variants === []) {
            throw new DomainException('A product must have at least one variant.');
        }

        $slug = $this->resolveSlug($data->slug, $data->name, $existing?->slug);
        $schedule = $this->resolveSchedule($data->status, $data->publishAt, $existing);

        $meta = array_merge($existing?->meta ?? [], $data->meta, [
            'sku_pattern' => $data->skuPattern,
        ]);
        unset($meta['variant_options'], $meta['specifications']);

        if ($data->type === 'variable' && $data->sku !== null && trim($data->sku) !== '') {
            $meta['sku_prefix'] = trim($data->sku);
        } else {
            unset($meta['sku_prefix']);
        }

        $attributes = [
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'type' => $data->type,
            'backorder_policy' => $data->backorderPolicy,
            'status' => $schedule['status'],
            'visibility' => $data->visibility,
            'brand_uuid' => $data->brandUuid,
            'seller_uuid' => $data->sellerUuid,
            'attribute_set_id' => $data->attributeSetId,
            'publish_at' => $schedule['publish_at'],
            'published_at' => $schedule['published_at'],
            'meta' => $meta,
        ];

        if ($existing === null) {
            $product = Product::query()->create($attributes);
        } else {
            $product = $existing;
            $product->update($attributes);
        }

        if ($existing !== null && $existingType === 'variable' && $data->type === 'simple') {
            $this->productTypeChangeGuard->assertCanBecomeSimple(
                $existing,
                array_values(array_filter(array_map(
                    static fn (array $row): ?string => self::nullableString($row['uuid'] ?? null),
                    $variants,
                ))),
            );
        }

        $this->syncVariants($product, $variants, $data, $existing === null);
        $this->syncRelations($product, $data->categoryIds, $data->collectionIds, $data->tagIds, $data->mediaUuids);
        $this->syncAssignedAttributes($product, $data);
        $this->syncGeneratedMatrix($product, $data);
        if (in_array($product->status, ['published', 'scheduled'], true)) {
            $this->publishGuard->assertCanPublish($product->fresh(['variants', 'productAttributes']));
        }
        $this->syncSeo($product, $data->seo);

        $this->slugService->register($slug, Product::SEO_ENTITY_TYPE, $product->uuid, $product->tenant_id);
        $this->searchIndexer->index($product->fresh(['variants', 'categories']));

        return $product->fresh([
            'variants',
            'media',
            'categories',
            'collections',
            'tags',
            'attributeValues.attribute',
            'attributeValues.attributeValue',
            'productAttributes',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(
        Product $product,
        array $variants,
        SaveProductWorkspaceData $data,
        bool $isProductCreate,
    ): void {
        $existing = $product->variants()->get()->keyBy('uuid');
        $keptUuids = [];
        $allocatedSkus = [];
        $defaultAssigned = false;
        $skuGenerator = new VariantSkuGenerator(function () use ($product, &$allocatedSkus): array {
            return [
                ...ProductVariant::query()
                    ->where('tenant_id', $product->tenant_id)
                    ->whereNotNull('sku')
                    ->pluck('sku')
                    ->all(),
                ...array_keys($allocatedSkus),
            ];
        });
        $skuPrefix = $this->skuPrefix($data, $product);
        $product->variants()->update(['is_default' => false]);

        foreach (array_values($variants) as $position => $row) {
            $uuid = self::nullableString($row['uuid'] ?? null);
            $variant = $uuid !== null ? $existing->get($uuid) : null;
            $wasTracked = $variant !== null && (bool) $variant->track_inventory;
            $isNewVariant = $variant === null;

            $options = is_array($row['options'] ?? null) ? $row['options'] : [];
            $typedSku = self::nullableString($row['sku'] ?? null);
            $generatedSku = $skuGenerator->allocate(
                $skuPrefix,
                array_values(array_map('strval', array_values($options))),
                $typedSku,
            );
            $this->assertSkuAvailable($product, $variant, $generatedSku->sku, $allocatedSkus, $position);
            $allocatedSkus[$generatedSku->sku] = true;
            $isDefault = ! $defaultAssigned && ((bool) ($row['isDefault'] ?? false) || $position === 0);
            if ($isDefault) {
                $defaultAssigned = true;
            }
            $status = self::normalizeVariantStatus($row['status'] ?? 'active');
            $meta = is_array($variant?->meta) ? $variant->meta : [];
            unset($meta['options']);
            $meta = array_filter(array_merge($meta, [
                'image_media_uuid' => self::nullableString($row['imageMediaUuid'] ?? $meta['image_media_uuid'] ?? null),
                'barcode' => self::nullableString($row['barcode'] ?? $meta['barcode'] ?? null),
                'cost' => self::nullableString($row['cost'] ?? $meta['cost'] ?? null),
                'weight' => self::nullableString($row['weight'] ?? $meta['weight'] ?? null),
                'status' => $status,
            ]), static fn ($value) => $value !== null && $value !== '');

            $attributes = [
                'sku' => $generatedSku->sku,
                'sku_is_auto' => $generatedSku->isAuto,
                'track_inventory' => $data->trackInventory,
                'name' => self::nullableString($row['name'] ?? null) ?: $product->name,
                'price' => self::toMinorUnits($row['price'] ?? 0),
                'compare_at_price' => self::nullableMinorUnits($row['comparePrice'] ?? null),
                'is_default' => $isDefault,
                'position' => $position,
                'meta' => $meta,
            ];

            if ($variant === null) {
                $variant = ProductVariant::query()->create(array_merge($attributes, [
                    'product_id' => $product->id,
                ]));
            } else {
                $variant->update($attributes);
            }

            $keptUuids[] = $variant->uuid;

            if (! $data->trackInventory) {
                continue;
            }

            $quantity = $this->submittedOnHand($row, $data, $product->type === 'simple');

            if (($isProductCreate || (! $isNewVariant && ! $wasTracked)) && $quantity === null) {
                throw ValidationException::withMessages([
                    "workspace_payload.variants.{$position}.onHand" => 'On-hand quantity is required when enabling inventory tracking.',
                ]);
            }

            if ($quantity !== null) {
                $this->inventoryService->setOnHand($variant->uuid, $quantity, 'Product workspace');
            } elseif ($isNewVariant) {
                $this->inventoryService->setOnHand($variant->uuid, 0, 'Product workspace');
            }
        }

        if ($keptUuids !== [] && ! $data->generateVariants && ! $this->hasPostedIdentities($data)) {
            $product->variants()
                ->whereNotIn('uuid', $keptUuids)
                ->each(static fn (ProductVariant $variant) => $variant->delete());
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function submittedOnHand(array $row, SaveProductWorkspaceData $data, bool $isSimple): ?int
    {
        $value = $row['onHand'] ?? $row['on_hand'] ?? ($isSimple ? $data->onHand : null);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, bool>  $allocatedSkus
     */
    private function assertSkuAvailable(
        Product $product,
        ?ProductVariant $variant,
        string $sku,
        array $allocatedSkus,
        int $position,
    ): void {
        $duplicateInSave = isset($allocatedSkus[$sku]);
        $duplicateInDatabase = ProductVariant::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('sku', $sku)
            ->when($variant !== null, static fn ($query) => $query->whereKeyNot($variant->getKey()))
            ->exists();

        if ($duplicateInSave || $duplicateInDatabase) {
            throw ValidationException::withMessages([
                "workspace_payload.variants.{$position}.sku" => "The SKU [{$sku}] has already been taken.",
            ]);
        }
    }

    private function skuPrefix(SaveProductWorkspaceData $data, Product $product): string
    {
        if ($data->sku !== null && trim($data->sku) !== '') {
            return $data->sku;
        }

        if ($data->skuPattern !== null
            && ! str_contains($data->skuPattern, '{')
            && strtolower(trim($data->skuPattern)) !== 'random') {
            return $data->skuPattern;
        }

        return $product->name;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function syncProductAttributeValues(Product $product, array $values): void
    {
        $this->attributeValueLinker->syncProductLevel($product, $values);
    }

    private function syncAssignedAttributes(Product $product, SaveProductWorkspaceData $data): void
    {
        $product->loadMissing('attributeSet.attributes');
        $this->productAttributeSetSync->syncProductAttributesFromSet($product);

        if ($data->productAttributes === []) {
            $this->syncProductAttributeValues($product, $data->attributeValues);

            return;
        }

        foreach (array_values($data->productAttributes) as $position => $row) {
            $attributeId = (int) ($row['attributeId'] ?? 0);
            if ($attributeId <= 0) {
                continue;
            }

            $attribute = Attribute::query()->find($attributeId);
            if ($attribute === null) {
                continue;
            }

            $usedForVariations = $data->type === 'simple'
                ? false
                : (bool) ($row['usedForVariations'] ?? false);

            if ($usedForVariations && $attribute->type !== 'select') {
                throw ValidationException::withMessages([
                    "workspace_payload.productAttributes.{$position}.usedForVariations" => 'Used for variations is only allowed on select attributes.',
                ]);
            }

            ProductAttribute::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_id' => $attributeId,
                ],
                [
                    'used_for_variations' => $usedForVariations,
                    'position' => (int) ($row['position'] ?? $position),
                ],
            );

            $valueIds = array_values(array_unique(array_map(
                'intval',
                is_array($row['valueIds'] ?? null) ? $row['valueIds'] : [],
            )));
            $valueIds = array_values(array_filter($valueIds, static fn (int $id): bool => $id > 0));

            foreach (is_array($row['newLabels'] ?? null) ? $row['newLabels'] : [] as $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }

                $value = $this->attributeValueLinker->resolveOrCreateAttributeValue($attribute, $label);
                if (! in_array($value->id, $valueIds, true)) {
                    $valueIds[] = $value->id;
                }
            }

            $this->attributeValueLinker->syncProductLevelValues($product, $attribute, $valueIds);
        }
    }

    private function syncGeneratedMatrix(Product $product, SaveProductWorkspaceData $data): void
    {
        if (! $this->shouldGenerate($product, $data)) {
            return;
        }

        $result = $this->variantMatrixGenerator->generate($product->fresh());
        $this->fillGeneratedSkus($product, $data, $result['create']);
        $this->applyGeneratedDrops($product, $data, $result);
        $this->ensureDefaultVariant($product);
    }

    private function shouldGenerate(Product $product, SaveProductWorkspaceData $data): bool
    {
        if ($data->type !== 'variable') {
            return false;
        }

        $axes = $product->productAttributes()
            ->where('used_for_variations', true)
            ->orderBy('position')
            ->get();

        if ($axes->isEmpty()) {
            return false;
        }

        foreach ($axes as $axis) {
            $hasMembership = ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $axis->attribute_id)
                ->whereNull('product_variant_id')
                ->whereNotNull('attribute_value_id')
                ->exists();

            if (! $hasMembership) {
                return false;
            }
        }

        return $data->generateVariants || $this->hasPostedIdentities($data);
    }

    private function hasPostedIdentities(SaveProductWorkspaceData $data): bool
    {
        foreach ($data->variants as $row) {
            $ids = $row['valueIds'] ?? $row['attributeValueIds'] ?? [];
            if (is_array($ids) && $ids !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, ProductVariant>  $created
     */
    private function fillGeneratedSkus(Product $product, SaveProductWorkspaceData $data, array $created): void
    {
        if ($created === []) {
            return;
        }

        $allocatedSkus = [];
        $skuGenerator = new VariantSkuGenerator(function () use ($product, &$allocatedSkus): array {
            return [
                ...ProductVariant::query()
                    ->where('tenant_id', $product->tenant_id)
                    ->whereNotNull('sku')
                    ->pluck('sku')
                    ->all(),
                ...array_keys($allocatedSkus),
            ];
        });
        $prefix = $this->skuPrefix($data, $product);

        foreach ($created as $variant) {
            $typedSku = self::nullableString($variant->sku);
            if ($typedSku !== null) {
                continue;
            }

            $codes = $this->axisCodesForVariant($product, $variant);
            $generated = $skuGenerator->allocate($prefix, $codes, null);
            $this->assertSkuAvailable($product, $variant, $generated->sku, $allocatedSkus, 0);
            $allocatedSkus[$generated->sku] = true;

            $variant->update([
                'sku' => $generated->sku,
                'sku_is_auto' => $generated->isAuto,
                'track_inventory' => $data->trackInventory,
            ]);

            if ($data->trackInventory) {
                $this->inventoryService->setOnHand($variant->uuid, 0, 'Product workspace');
            }
        }
    }

    /**
     * @return list<string>
     */
    private function axisCodesForVariant(Product $product, ProductVariant $variant): array
    {
        $axes = $product->productAttributes()
            ->where('used_for_variations', true)
            ->orderBy('position')
            ->get();

        $rows = ProductAttributeValue::query()
            ->with('attributeValue')
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->whereNotNull('attribute_value_id')
            ->get()
            ->keyBy('attribute_id');

        $codes = [];
        foreach ($axes as $axis) {
            $code = $rows->get($axis->attribute_id)?->attributeValue?->code;
            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * @param  array{keep: array<string, ProductVariant>, create: array<string, ProductVariant>, drop: array<string, ProductVariant>}  $result
     */
    private function applyGeneratedDrops(Product $product, SaveProductWorkspaceData $data, array $result): void
    {
        if ($data->type === 'simple' || $result['drop'] === []) {
            return;
        }

        $dropUuids = [];
        foreach ($result['drop'] as $key => $variant) {
            if ($key === '' && $variant->is_default && $data->type === 'simple') {
                continue;
            }

            $dropUuids[] = $variant->uuid;
        }

        if ($dropUuids === []) {
            return;
        }

        $keptUuids = $product->variants()
            ->whereNotIn('uuid', $dropUuids)
            ->pluck('uuid')
            ->all();

        $this->productTypeChangeGuard->assertCanBecomeSimple($product, $keptUuids);

        $product->variants()
            ->whereIn('uuid', $dropUuids)
            ->each(static fn (ProductVariant $variant) => $variant->delete());
    }

    private function ensureDefaultVariant(Product $product): void
    {
        $remaining = $product->variants()->get();
        if ($remaining->isEmpty() || $remaining->contains(static fn (ProductVariant $variant): bool => (bool) $variant->is_default)) {
            return;
        }

        $remaining->first()?->update(['is_default' => true]);
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $collectionIds
     * @param  list<int>  $tagIds
     * @param  list<string>  $mediaUuids
     */
    private function syncRelations(Product $product, array $categoryIds, array $collectionIds, array $tagIds, array $mediaUuids): void
    {
        $product->categories()->sync($categoryIds);
        $product->collections()->sync($collectionIds);
        $product->tags()->sync($tagIds);

        $product->media()->delete();

        foreach (array_values($mediaUuids) as $position => $mediaUuid) {
            if ($mediaUuid === '') {
                continue;
            }

            ProductMedia::query()->create([
                'product_id' => $product->id,
                'media_uuid' => $mediaUuid,
                'position' => $position,
                'is_primary' => $position === 0,
            ]);
        }
    }

    private function syncSeo(Product $product, ?SeoData $seo): void
    {
        if ($seo === null) {
            return;
        }

        $hasContent = $seo->metaTitle || $seo->metaDescription || $seo->metaKeywords
            || $seo->canonicalUrl || $seo->ogImageMediaUuid;

        if (! $hasContent) {
            $this->seoService->deleteForEntity(Product::SEO_ENTITY_TYPE, $product->uuid);

            return;
        }

        $this->seoService->setForEntity(
            Product::SEO_ENTITY_TYPE,
            $product->uuid,
            $seo->toSeoArray(),
        );
    }

    /**
     * @return array{status: string, publish_at: ?Carbon, published_at: ?Carbon}
     */
    private function resolveSchedule(string $status, ?string $publishAt, ?Product $existing = null): array
    {
        if ($status === 'scheduled') {
            if ($publishAt === null || $publishAt === '') {
                throw new DomainException('Scheduled products require a publish date.');
            }

            return [
                'status' => 'scheduled',
                'publish_at' => Carbon::parse($publishAt),
                'published_at' => null,
            ];
        }

        if ($status === 'published') {
            return [
                'status' => 'published',
                'publish_at' => null,
                'published_at' => $existing?->published_at ?? now(),
            ];
        }

        return [
            'status' => $status,
            'publish_at' => null,
            'published_at' => $existing?->published_at,
        ];
    }

    private function resolveSlug(?string $requested, string $name, ?string $current = null): string
    {
        if ($requested !== null && $requested !== '') {
            return Str::slug($requested);
        }

        if ($current !== null && $current !== '') {
            return $current;
        }

        return $this->slugService->generate($name, Product::SEO_ENTITY_TYPE);
    }

    private function findOrFail(string $uuid): Product
    {
        $product = Product::query()->where('uuid', $uuid)->first();

        if ($product === null) {
            throw new EntityNotFoundException("Product [{$uuid}] not found.");
        }

        return $product;
    }

    private function productPath(string $slug): string
    {
        return '/products/'.ltrim($slug, '/');
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private static function toMinorUnits(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round(((float) $value) * 100);
    }

    private static function nullableMinorUnits(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::toMinorUnits($value);
    }

    private static function normalizeVariantStatus(mixed $status): string
    {
        $normalized = strtolower((string) $status);

        return in_array($normalized, ['active', 'draft', 'archived'], true) ? $normalized : 'active';
    }
}
