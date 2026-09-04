<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Product\DTO\SaveProductWorkspaceData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Events\ProductUnpublished;
use Commerce\Product\Events\ProductUpdated;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductWorkspaceSaveService
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly SeoServiceInterface $seoService,
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly ProductSearchIndexer $searchIndexer,
        private readonly VariantOptionAttributeProvisioner $variantOptionAttributeProvisioner,
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
        if ($data->variants === []) {
            throw new DomainException('A product must have at least one variant.');
        }

        $slug = $this->resolveSlug($data->slug, $data->name, $existing?->slug);
        $schedule = $this->resolveSchedule($data->status, $data->publishAt, $existing);
        $type = count($data->variants) > 1 ? 'variable' : 'simple';

        $meta = array_merge($existing?->meta ?? [], $data->meta, [
            'variant_options' => $data->variantOptions,
            'sku_pattern' => $data->skuPattern,
        ]);

        $attributes = [
            'name' => $data->name,
            'slug' => $slug,
            'description' => $data->description,
            'type' => $type,
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

        $this->syncVariants($product, $data->variants);
        $this->syncRelations($product, $data->categoryIds, $data->collectionIds, $data->tagIds, $data->mediaUuids);
        $this->syncProductAttributeValues($product, $data->attributeValues);
        $this->syncSeo($product, $data->seo);

        $this->slugService->register($slug, Product::SEO_ENTITY_TYPE, $product->uuid, $product->tenant_id);
        $this->searchIndexer->index($product->fresh(['variants', 'categories']));

        return $product->fresh(['variants', 'media', 'categories', 'collections', 'tags', 'attributeValues.attribute']);
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $existing = $product->variants()->get()->keyBy('uuid');
        $keptUuids = [];
        $defaultAssigned = false;
        $product->variants()->update(['is_default' => false]);

        foreach (array_values($variants) as $position => $row) {
            $uuid = self::nullableString($row['uuid'] ?? null);
            $variant = $uuid !== null ? $existing->get($uuid) : null;

            $options = is_array($row['options'] ?? null) ? $row['options'] : [];
            $isDefault = ! $defaultAssigned && ((bool) ($row['isDefault'] ?? false) || $position === 0);
            if ($isDefault) {
                $defaultAssigned = true;
            }
            $status = self::normalizeVariantStatus($row['status'] ?? 'active');

            $attributes = [
                'sku' => self::nullableString($row['sku'] ?? null),
                'name' => self::nullableString($row['name'] ?? null) ?: $product->name,
                'price' => self::toMinorUnits($row['price'] ?? 0),
                'compare_at_price' => self::nullableMinorUnits($row['comparePrice'] ?? null),
                'is_default' => $isDefault,
                'position' => $position,
                'meta' => array_filter([
                    'options' => $options,
                    'image_media_uuid' => self::nullableString($row['imageMediaUuid'] ?? null),
                    'barcode' => self::nullableString($row['barcode'] ?? null),
                    'cost' => self::nullableString($row['cost'] ?? null),
                    'weight' => self::nullableString($row['weight'] ?? null),
                    'status' => $status,
                ], static fn ($value) => $value !== null && $value !== []),
            ];

            if ($variant === null) {
                $variant = ProductVariant::query()->create(array_merge($attributes, [
                    'product_id' => $product->id,
                ]));
            } else {
                $variant->update($attributes);
            }

            $keptUuids[] = $variant->uuid;
            $this->syncVariantAttributeValues($product, $variant, $options);
        }

        if ($keptUuids !== []) {
            $product->variants()
                ->whereNotIn('uuid', $keptUuids)
                ->each(static fn (ProductVariant $variant) => $variant->delete());
        }
    }

    /**
     * @param  array<string, string>  $options
     */
    private function syncVariantAttributeValues(Product $product, ProductVariant $variant, array $options): void
    {
        ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant->id)
            ->delete();

        if ($options === []) {
            return;
        }

        $attributeMap = $this->variantOptionAttributeProvisioner->resolve($product, array_keys($options));

        foreach ($options as $optionKey => $value) {
            $attributeId = $attributeMap[strtolower((string) $optionKey)] ?? null;

            if ($attributeId === null || $value === '') {
                continue;
            }

            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $attributeId,
                'product_variant_id' => $variant->id,
                'value' => (string) $value,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function syncProductAttributeValues(Product $product, array $values): void
    {
        $product->attributeValues()->whereNull('product_variant_id')->delete();

        foreach ($values as $attributeId => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $stored = is_array($value) ? json_encode(array_values($value)) : (string) $value;

            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => (int) $attributeId,
                'value' => $stored,
            ]);
        }
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
