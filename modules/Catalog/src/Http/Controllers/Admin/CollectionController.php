<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\CollectionServiceInterface;
use Commerce\Catalog\DTO\CreateCollectionData;
use Commerce\Catalog\DTO\UpdateCollectionData;
use Commerce\Catalog\Http\Requests\StoreCollectionRequest;
use Commerce\Catalog\Http\Requests\UpdateCollectionRequest;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Models\Tag;
use Commerce\Catalog\Services\CollectionQueryService;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Commerce\Catalog\Support\CollectionRuleNormalizer;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CollectionController extends Controller
{
    public function __construct(
        private readonly CollectionQueryService $queryService,
        private readonly CollectionServiceInterface $collectionService,
        private readonly CatalogMediaResolver $mediaResolver,
        private readonly SeoServiceInterface $seoService,
        private readonly CollectionRuleNormalizer $ruleNormalizer,
    ) {}

    public function index(): View
    {
        $collections = $this->queryService->paginate();
        $coverUrls = [];

        foreach ($collections as $collection) {
            $url = $this->mediaResolver->url($collection->cover_media_uuid);

            if ($url !== null) {
                $coverUrls[$collection->uuid] = $url;
            }
        }

        return view('catalog::admin.collections.index', [
            'collections' => $collections,
            'coverUrls' => $coverUrls,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $this->collectionService->create(new CreateCollectionData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            coverMediaUuid: $request->validated('cover_media_uuid'),
            type: $request->validated('type', Collection::TYPE_MANUAL),
            rules: $this->normalizedRules($request),
            seo: $request->validated('seo'),
        ));

        return redirect()->route('admin.catalog.collections.index')->with('status', 'Collection created.');
    }

    public function edit(Collection $collection): View
    {
        return view('catalog::admin.collections.edit', [
            'collection' => $collection,
            'coverUrl' => $this->mediaResolver->url($collection->cover_media_uuid),
            'seo' => $this->seoService->getForEntity(Collection::SEO_ENTITY_TYPE, $collection->uuid),
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $this->collectionService->update($collection->uuid, new UpdateCollectionData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            coverMediaUuid: $request->validated('cover_media_uuid'),
            type: $request->validated('type'),
            rules: $this->normalizedRules($request),
            seo: $request->validated('seo'),
        ));

        return redirect()->route('admin.catalog.collections.index')->with('status', 'Collection updated.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->collectionService->delete($collection->uuid);

        return redirect()->route('admin.catalog.collections.index')->with('status', 'Collection deleted.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizedRules(StoreCollectionRequest|UpdateCollectionRequest $request): ?array
    {
        return $this->ruleNormalizer->normalize($request->validated('rules'));
    }
}
