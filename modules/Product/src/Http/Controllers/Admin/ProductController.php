<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Tag;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\DTO\SeoData;
use Commerce\Product\DTO\UpdateProductData;
use Commerce\Product\Http\Requests\StoreProductRequest;
use Commerce\Product\Http\Requests\StoreVariantRequest;
use Commerce\Product\Http\Requests\UpdateProductRequest;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $queryService,
        private readonly ProductServiceInterface $productService,
        private readonly MediaQueryServiceInterface $mediaQueryService,
        private readonly SeoServiceInterface $seoService,
    ) {}

    public function index(Request $request): View
    {
        $products = $this->queryService->paginate(
            search: $request->string('search')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
        );

        $imageUrls = [];
        foreach ($products as $product) {
            $primary = $product->media->firstWhere('is_primary', true) ?? $product->media->first();
            if ($primary) {
                $imageUrls[$product->uuid] = $this->mediaQueryService->getUrl($primary->media_uuid, 'thumbnail')
                    ?? $this->mediaQueryService->getUrl($primary->media_uuid);
            }
        }

        return view('product::admin.products.index', [
            'products' => $products,
            'imageUrls' => $imageUrls,
            'statuses' => config('product.statuses', []),
        ]);
    }

    public function create(): View
    {
        return view('product::admin.products.create', $this->formOptions());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($this->mapProductData($request));

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product created.');
    }

    public function edit(string $product): View
    {
        $model = Product::query()
            ->with(['variants', 'media', 'categories', 'tags', 'attributeValues', 'attributeSet.attributes'])
            ->where('uuid', $product)
            ->firstOrFail();

        $mediaPreviews = [];
        foreach ($model->media as $item) {
            $mediaPreviews[$item->media_uuid] = $this->mediaQueryService->getUrl($item->media_uuid, 'thumbnail')
                ?? $this->mediaQueryService->getUrl($item->media_uuid);
        }

        $seo = $this->seoService->getForEntity(Product::SEO_ENTITY_TYPE, $model->uuid);

        $stockLevels = [];
        if (app()->bound(InventoryQueryServiceInterface::class)) {
            $stockLevels = app(InventoryQueryServiceInterface::class)->levelsForPurchasables(
                $model->variants->pluck('uuid')->all(),
            );
        }

        return view('product::admin.products.edit', array_merge(
            $this->formOptions($model),
            [
                'product' => $model,
                'mediaPreviews' => $mediaPreviews,
                'seo' => $seo,
                'stockLevels' => $stockLevels,
            ],
        ));
    }

    public function update(UpdateProductRequest $request, string $product): RedirectResponse
    {
        $this->productService->update($product, $this->mapProductData($request, true));

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product updated.');
    }

    public function destroy(string $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product deleted.');
    }

    public function publish(string $product): RedirectResponse
    {
        $this->productService->publish($product);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product published.');
    }

    public function archive(string $product): RedirectResponse
    {
        $this->productService->archive($product);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product archived.');
    }

    public function storeVariant(StoreVariantRequest $request, string $product): RedirectResponse
    {
        $this->productService->addVariant(new CreateVariantData(
            productUuid: $product,
            sku: $request->validated('sku'),
            name: $request->validated('name'),
            price: $this->toCents($request->input('price')),
            compareAtPrice: $request->filled('compare_at_price') ? $this->toCents($request->input('compare_at_price')) : null,
            isDefault: (bool) $request->validated('is_default', false),
        ));

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Variant added.');
    }

    public function destroyVariant(string $product, string $variant): RedirectResponse
    {
        $this->productService->deleteVariant($variant);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Variant deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Product $product = null): array
    {
        $attributeSetId = (int) old('attribute_set_id', $product?->attribute_set_id ?? 0);
        $attributeSet = $attributeSetId > 0
            ? AttributeSet::query()->with('attributes')->find($attributeSetId)
            : null;

        return [
            'types' => config('product.types', []),
            'statuses' => config('product.statuses', []),
            'visibilities' => config('product.visibilities', []),
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'attributeSets' => AttributeSet::query()->orderBy('name')->get(),
            'attributes' => $attributeSet?->attributes ?? collect(),
            'attributeValues' => $product?->attributeValues->keyBy('attribute_id') ?? collect(),
            'seo' => null,
        ];
    }

    private function mapProductData(StoreProductRequest|UpdateProductRequest $request, bool $isUpdate = false): CreateProductData|UpdateProductData
    {
        $seoInput = $request->input('seo', []);

        $data = [
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug'),
            'description' => $request->validated('description'),
            'type' => $request->validated('type'),
            'status' => $request->validated('status'),
            'visibility' => $request->validated('visibility'),
            'brandUuid' => $request->validated('brand_uuid'),
            'attributeSetId' => $request->validated('attribute_set_id'),
            'sku' => $request->validated('sku'),
            'price' => $this->toCents($request->input('price', 0)),
            'compareAtPrice' => $request->filled('compare_at_price') ? $this->toCents($request->input('compare_at_price')) : null,
            'publishAt' => $request->validated('publish_at'),
            'categoryIds' => array_map('intval', $request->validated('category_ids', [])),
            'tagIds' => array_map('intval', $request->validated('tag_ids', [])),
            'mediaUuids' => array_values(array_filter($request->validated('media_uuids', []))),
            'attributeValues' => $request->input('attributes', []),
            'seo' => new SeoData(
                metaTitle: $seoInput['meta_title'] ?? null,
                metaDescription: $seoInput['meta_description'] ?? null,
                metaKeywords: $seoInput['meta_keywords'] ?? null,
                canonicalUrl: $seoInput['canonical_url'] ?? null,
                ogImageMediaUuid: $seoInput['og_image_media_uuid'] ?? null,
            ),
        ];

        return $isUpdate ? new UpdateProductData(...$data) : new CreateProductData(...$data);
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
