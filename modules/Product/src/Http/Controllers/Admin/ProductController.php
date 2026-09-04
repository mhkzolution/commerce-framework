<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Tag;
use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Marketplace\Models\Seller;
use Commerce\Media\Models\Media;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Http\Requests\BulkDeleteProductRequest;
use Commerce\Product\Http\Requests\StoreProductRequest;
use Commerce\Product\Http\Requests\StoreVariantRequest;
use Commerce\Product\Http\Requests\UpdateProductRequest;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductQueryService;
use Commerce\Product\Services\ProductWorkspaceSaveService;
use Commerce\Product\Services\ProductWorkspaceStateBuilder;
use Commerce\Product\Services\VariantOptionPresetService;
use Commerce\Product\Support\WorkspacePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductQueryService $queryService,
        private readonly ProductServiceInterface $productService,
        private readonly ProductWorkspaceSaveService $workspaceSaveService,
        private readonly ProductWorkspaceStateBuilder $workspaceStateBuilder,
        private readonly MediaQueryServiceInterface $mediaQueryService,
        private readonly SeoServiceInterface $seoService,
        private readonly AuthorizationServiceInterface $authorization,
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
            'canDelete' => $this->authorization->can($request->user(), 'product.product.delete'),
            'canImport' => $this->authorization->can($request->user(), 'product.product.create'),
        ]);
    }

    public function create(): View
    {
        return view('product::admin.products.create', array_merge(
            $this->formOptions(),
            ['initialState' => $this->workspaceStateBuilder->build()],
        ));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->workspaceSaveService->create(
            WorkspacePayload::fromRequest($request),
        );

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product created.');
    }

    public function edit(string $product): View
    {
        $model = Product::query()
            ->with(['variants', 'media', 'categories', 'collections', 'tags', 'attributeValues', 'attributeSet.attributes'])
            ->where('uuid', $product)
            ->firstOrFail();

        $seo = $this->seoService->getForEntity(Product::SEO_ENTITY_TYPE, $model->uuid);
        $stockLevels = $this->workspaceStateBuilder->stockLevelsFor($model);

        return view('product::admin.products.edit', array_merge(
            $this->formOptions($model, $stockLevels),
            [
                'product' => $model,
                'seo' => $seo,
                'stockLevels' => $stockLevels,
                'initialState' => $this->workspaceStateBuilder->build($model, $stockLevels),
            ],
        ));
    }

    public function update(UpdateProductRequest $request, string $product): RedirectResponse
    {
        $this->workspaceSaveService->update(
            $product,
            WorkspacePayload::fromRequest($request),
        );

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

    public function bulkDestroy(BulkDeleteProductRequest $request): RedirectResponse
    {
        $deleted = $this->productService->deleteMany($request->validated('uuids'));

        $filters = array_filter([
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
        ]);

        return redirect()
            ->route('admin.products.index', $filters)
            ->with('status', $deleted === 1 ? '1 product deleted.' : $deleted.' products deleted.');
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
     * @param  array<string, mixed>  $stockLevels
     * @return array<string, mixed>
     */
    private function formOptions(?Product $product = null, array $stockLevels = []): array
    {
        $attributeSetId = (int) old(
            'attribute_set_id',
            $product?->attribute_set_id ?? $this->defaultAttributeSetId() ?? 0,
        );
        $attributeSet = $attributeSetId > 0
            ? AttributeSet::query()->with('attributes')->find($attributeSetId)
            : null;

        $mediaUuids = array_values(array_filter(old('media_uuids', $product?->media->pluck('media_uuid')->all() ?? [])));
        $mediaPreviews = [];
        $mediaTypes = [];
        foreach ($mediaUuids as $uuid) {
            $mediaPreviews[$uuid] = $this->mediaQueryService->getUrl($uuid, 'thumbnail')
                ?? $this->mediaQueryService->getUrl($uuid);
            $mediaTypes[$uuid] = Media::query()->where('uuid', $uuid)->value('media_type') ?? 'image';
        }

        return [
            'types' => config('product.types', []),
            'statuses' => config('product.statuses', []),
            'visibilities' => config('product.visibilities', []),
            'brands' => Brand::query()->orderBy('name')->get(),
            'sellers' => $this->activeSellers(),
            'categories' => Category::query()->orderBy('name')->get(),
            'collections' => \Commerce\Catalog\Models\Collection::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'attributeSets' => AttributeSet::query()->orderBy('name')->get(),
            'defaultAttributeSetId' => $this->defaultAttributeSetId(),
            'attributeSetsPayload' => $this->attributeSetsPayload(),
            'attributeOptionPresets' => config('product.attribute_option_presets', []),
            'optionPresets' => app(VariantOptionPresetService::class)->presetMap(),
            'attributes' => $attributeSet?->attributes ?? collect(),
            'attributeValues' => $product?->attributeValues->keyBy('attribute_id') ?? collect(),
            'mediaPreviews' => $mediaPreviews,
            'mediaTypes' => $mediaTypes,
            'seo' => null,
            'inventoryEnabled' => app()->bound(InventoryServiceInterface::class),
            'stockLevels' => $stockLevels,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attributeSetsPayload(): array
    {
        return AttributeSet::query()
            ->with('attributes')
            ->orderBy('name')
            ->get()
            ->map(static function (AttributeSet $set): array {
                return [
                    'id' => $set->id,
                    'name' => $set->name,
                    'attributes' => $set->attributes->map(static function ($attribute): array {
                        return [
                            'id' => $attribute->id,
                            'name' => $attribute->name,
                            'type' => $attribute->type,
                            'options' => $attribute->options ?? [],
                            'is_required' => (bool) ($attribute->pivot?->is_required ?? $attribute->is_required),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function defaultAttributeSetId(): ?int
    {
        $code = (string) config('product.default_attribute_set_code', '');

        if ($code !== '') {
            $id = AttributeSet::query()->where('code', $code)->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        $fallbackId = AttributeSet::query()->orderBy('name')->value('id');

        return $fallbackId !== null ? (int) $fallbackId : null;
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * @return Collection<int, object>
     */
    private function activeSellers(): Collection
    {
        if (! class_exists(Seller::class)) {
            return collect();
        }

        return Seller::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
