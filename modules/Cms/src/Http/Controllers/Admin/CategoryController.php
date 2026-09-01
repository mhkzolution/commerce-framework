<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\CreateCategoryData;
use Commerce\Cms\DTO\UpdateCategoryData;
use Commerce\Cms\Http\Requests\StoreCategoryRequest;
use Commerce\Cms\Http\Requests\UpdateCategoryRequest;
use Commerce\Cms\Models\Category;
use Commerce\Cms\Services\CategoryService;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly SeoServiceInterface $seoService,
    ) {}

    public function index(): View
    {
        return view('cms::admin.categories.index', [
            'items' => Category::query()->with('parent')->orderBy('position')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.categories.create', [
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($this->toCreateData($request));

        return redirect()->route('admin.cms.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('cms::admin.categories.edit', [
            'item' => $category,
            'parents' => Category::query()->where('id', '!=', $category->id)->orderBy('name')->get(),
            'seo' => $this->seoService->getForEntity(Category::SEO_ENTITY_TYPE, $category->uuid),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, new UpdateCategoryData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            imageMediaUuid: $request->validated('image_media_uuid'),
            parentId: $request->validated('parent_id') !== null ? (int) $request->validated('parent_id') : null,
            isActive: $request->boolean('is_active'),
            position: (int) $request->validated('position', 0),
            seo: $request->validated('seo'),
        ));

        return redirect()->route('admin.cms.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);

        return redirect()->route('admin.cms.categories.index')->with('status', 'Category deleted.');
    }

    private function toCreateData(StoreCategoryRequest $request): CreateCategoryData
    {
        return new CreateCategoryData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            imageMediaUuid: $request->validated('image_media_uuid'),
            parentId: $request->validated('parent_id') !== null ? (int) $request->validated('parent_id') : null,
            isActive: $request->boolean('is_active', true),
            position: (int) $request->validated('position', 0),
            seo: $request->validated('seo'),
        );
    }
}
