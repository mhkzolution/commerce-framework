<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\BrandServiceInterface;
use Commerce\Catalog\DTO\CreateBrandData;
use Commerce\Catalog\DTO\UpdateBrandData;
use Commerce\Catalog\Http\Requests\StoreBrandRequest;
use Commerce\Catalog\Http\Requests\UpdateBrandRequest;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Services\BrandQueryService;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BrandController extends Controller
{
    public function __construct(
        private readonly BrandQueryService $queryService,
        private readonly BrandServiceInterface $brandService,
        private readonly MediaQueryServiceInterface $mediaQueryService,
    ) {}

    public function index(): View
    {
        $brands = $this->queryService->paginate();
        $logoUrls = [];

        foreach ($brands as $brand) {
            if ($brand->logo_media_uuid) {
                $logoUrls[$brand->uuid] = $this->mediaQueryService->getUrl($brand->logo_media_uuid, 'thumbnail')
                    ?? $this->mediaQueryService->getUrl($brand->logo_media_uuid);
            }
        }

        return view('catalog::admin.brands.index', [
            'brands' => $brands,
            'logoUrls' => $logoUrls,
        ]);
    }

    public function create(): View
    {
        return view('catalog::admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->brandService->create(new CreateBrandData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            logoMediaUuid: $request->validated('logo_media_uuid'),
            isActive: (bool) $request->validated('is_active', true),
        ));

        return redirect()->route('admin.catalog.brands.index')->with('status', 'Brand created.');
    }

    public function edit(string $brand): View
    {
        return view('catalog::admin.brands.edit', [
            'brand' => Brand::query()->where('uuid', $brand)->firstOrFail(),
        ]);
    }

    public function update(UpdateBrandRequest $request, string $brand): RedirectResponse
    {
        $this->brandService->update($brand, new UpdateBrandData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            logoMediaUuid: $request->validated('logo_media_uuid'),
            isActive: (bool) $request->validated('is_active', true),
        ));

        return redirect()->route('admin.catalog.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(string $brand): RedirectResponse
    {
        $this->brandService->delete($brand);

        return redirect()->route('admin.catalog.brands.index')->with('status', 'Brand deleted.');
    }
}
