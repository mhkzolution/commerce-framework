<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Contracts\Seo\SlugServiceInterface;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\DTO\UpdateProductData;
use Commerce\Product\Events\ProductCreated;
use Commerce\Product\Events\ProductPublished;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductService extends BaseService implements ProductServiceInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly SeoServiceInterface $seoService,
        private readonly SlugServiceInterface $slugService,
        private readonly UrlRedirectServiceInterface $urlRedirectService,
        private readonly ProductSearchIndexer $searchIndexer,
    ) {}

    public function create(CreateProductData $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $slug = $this->resolveSlug($data->slug, $data->name);
            $schedule = $this->resolveSchedule($data->status, $data->publishAt);

            $product = Product::query()->create([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'type' => $data->type,
                'status' => $schedule['status'],
                'visibility' => $data->visibility,
                'brand_uuid' => $data->brandUuid,
                'seller_uuid' => $data->sellerUuid,
                'attribute_set_id' => $data->attributeSetId,
                'publish_at' => $schedule['publish_at'],
                'published_at' => $schedule['published_at'],
            ]);

            $this->createDefaultVariant($product, $data->sku, $data->price, $data->compareAtPrice);
            $this->syncRelations($product, $data->categoryIds, $data->tagIds, $data->mediaUuids);
            $this->syncAttributeValues($product, $data->attributeValues);
            $this->syncSeo($product, $data->seo);

            $this->eventBus->dispatch(new ProductCreated(
                productUuid: $product->uuid,
                type: $product->type,
            ));

            if ($product->fresh()->isPublished()) {
                $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));
            }

            $this->slugService->register($slug, Product::SEO_ENTITY_TYPE, $product->uuid, $product->tenant_id);
            $this->searchIndexer->index($product->fresh(['variants', 'categories']));

            return $product->fresh(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute']);
        });
    }

    public function update(string $uuid, UpdateProductData $data): Product
    {
        return DB::transaction(function () use ($uuid, $data): Product {
            $product = $this->findOrFail($uuid);
            $wasPublished = $product->isPublished();
            $oldSlug = $product->slug;
            $slug = $this->resolveSlug($data->slug ?? null, $data->name, $product->slug);
            $schedule = $this->resolveSchedule($data->status, $data->publishAt, $product);

            $product->update([
                'name' => $data->name,
                'slug' => $slug,
                'description' => $data->description,
                'type' => $data->type,
                'status' => $schedule['status'],
                'visibility' => $data->visibility,
                'brand_uuid' => $data->brandUuid,
                'seller_uuid' => $data->sellerUuid,
                'attribute_set_id' => $data->attributeSetId,
                'publish_at' => $schedule['publish_at'],
                'published_at' => $schedule['published_at'],
            ]);

            $defaultVariant = $product->defaultVariant();

            if ($defaultVariant !== null) {
                $defaultVariant->update([
                    'sku' => $data->sku,
                    'price' => $data->price,
                    'compare_at_price' => $data->compareAtPrice,
                ]);
            } else {
                $this->createDefaultVariant($product, $data->sku, $data->price, $data->compareAtPrice);
            }

            $this->syncRelations($product, $data->categoryIds, $data->tagIds, $data->mediaUuids);
            $this->syncAttributeValues($product, $data->attributeValues);
            $this->syncSeo($product, $data->seo);

            if (! $wasPublished && $product->fresh()->isPublished()) {
                $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));
            }

            if ($oldSlug !== $slug) {
                $this->urlRedirectService->createRedirect(
                    $this->productPath($oldSlug),
                    $this->productPath($slug),
                );
            }

            $this->slugService->register($slug, Product::SEO_ENTITY_TYPE, $product->uuid, $product->tenant_id);
            $this->searchIndexer->index($product->fresh(['variants', 'categories']));

            return $product->fresh(['variants', 'media', 'categories', 'tags', 'attributeValues.attribute']);
        });
    }

    public function delete(string $uuid): void
    {
        $product = $this->findOrFail($uuid);
        $this->seoService->deleteForEntity(Product::SEO_ENTITY_TYPE, $product->uuid);
        $this->slugService->unregister(Product::SEO_ENTITY_TYPE, $product->uuid);
        $this->searchIndexer->delete($product->uuid);
        $product->delete();
    }

    public function deleteMany(array $uuids): int
    {
        $deleted = 0;

        foreach (array_values(array_unique($uuids)) as $uuid) {
            try {
                $this->delete($uuid);
                $deleted++;
            } catch (EntityNotFoundException) {
                continue;
            }
        }

        return $deleted;
    }

    public function publish(string $uuid): Product
    {
        $product = $this->findOrFail($uuid);

        if ($product->isPublished()) {
            return $product;
        }

        $product->update([
            'status' => 'published',
            'published_at' => now(),
            'publish_at' => null,
        ]);

        $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));

        $this->searchIndexer->index($product->fresh(['variants', 'categories']));

        return $product->fresh();
    }

    public function archive(string $uuid): Product
    {
        $product = $this->findOrFail($uuid);
        $product->update(['status' => 'archived', 'publish_at' => null]);

        return $product->fresh();
    }

    public function publishScheduled(): int
    {
        $count = 0;

        Product::query()
            ->where('status', 'scheduled')
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->orderBy('id')
            ->each(function (Product $product) use (&$count): void {
                $product->update([
                    'status' => 'published',
                    'published_at' => $product->publish_at ?? now(),
                    'publish_at' => null,
                ]);

                $this->eventBus->dispatch(new ProductPublished(productUuid: $product->uuid));
                $count++;
            });

        return $count;
    }

    public function addVariant(CreateVariantData $data): ProductVariant
    {
        $product = $this->findOrFail($data->productUuid);

        if ($product->isSimple()) {
            throw new DomainException('Simple products cannot have multiple variants.');
        }

        if ($data->isDefault) {
            $product->variants()->update(['is_default' => false]);
        }

        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => $data->sku,
            'name' => $data->name,
            'price' => $data->price,
            'compare_at_price' => $data->compareAtPrice,
            'is_default' => $data->isDefault,
            'position' => $data->position,
        ]);
    }

    public function deleteVariant(string $uuid): void
    {
        $variant = ProductVariant::query()->where('uuid', $uuid)->first();

        if ($variant === null) {
            throw new EntityNotFoundException("Variant [{$uuid}] not found.");
        }

        if ($variant->product?->variants()->count() <= 1) {
            throw new DomainException('A product must have at least one variant.');
        }

        $variant->delete();
    }

    private function findOrFail(string $uuid): Product
    {
        $product = Product::query()->where('uuid', $uuid)->first();

        if ($product === null) {
            throw new EntityNotFoundException("Product [{$uuid}] not found.");
        }

        return $product;
    }

    private function createDefaultVariant(Product $product, ?string $sku, int $price, ?int $compareAtPrice): void
    {
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => $sku,
            'name' => $product->name,
            'price' => $price,
            'compare_at_price' => $compareAtPrice,
            'is_default' => true,
            'position' => 0,
        ]);
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $tagIds
     * @param  list<string>  $mediaUuids
     */
    private function syncRelations(Product $product, array $categoryIds, array $tagIds, array $mediaUuids): void
    {
        $product->categories()->sync($categoryIds);
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

    /**
     * @param  array<int, mixed>  $values
     */
    private function syncAttributeValues(Product $product, array $values): void
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

            $scheduledAt = Carbon::parse($publishAt);

            return [
                'status' => 'scheduled',
                'publish_at' => $scheduledAt,
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
            'published_at' => null,
        ];
    }

    private function resolveSlug(?string $requested, string $name, ?string $current = null): string
    {
        if ($requested !== null && $requested !== '') {
            $slug = Str::slug($requested);
        } elseif ($current !== null && $current !== '') {
            $slug = $current;
        } else {
            $slug = $this->slugService->generate($name, Product::SEO_ENTITY_TYPE);
        }

        return $slug;
    }

    private function productPath(string $slug): string
    {
        return '/products/'.ltrim($slug, '/');
    }
}
