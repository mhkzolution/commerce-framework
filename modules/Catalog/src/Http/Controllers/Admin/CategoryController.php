<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\CategoryServiceInterface;
use Commerce\Catalog\DTO\CreateCategoryData;
use Commerce\Catalog\DTO\UpdateCategoryData;
use Commerce\Catalog\Http\Requests\ReorderCategoryRequest;
use Commerce\Catalog\Http\Requests\StoreCategoryRequest;
use Commerce\Catalog\Http\Requests\UpdateCategoryRequest;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Services\CategoryQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
        private readonly CategoryServiceInterface $categoryService,
    ) {}

    public function index(): View
    {
        return view('catalog::admin.categories.index', [
            'tree' => $this->queryService->tree(),
        ]);
    }

    public function create(): View
    {
        return view('catalog::admin.categories.create', [
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create(new CreateCategoryData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            parentId: $request->validated('parent_id'),
            isActive: (bool) $request->validated('is_active', true),
            position: (int) $request->validated('position', 0),
        ));

        return redirect()->route('admin.catalog.categories.index')->with('status', 'Category created.');
    }

    public function edit(string $category): View
    {
        $model = Category::query()->where('uuid', $category)->firstOrFail();

        return view('catalog::admin.categories.edit', [
            'category' => $model,
            'parents' => Category::query()->where('id', '!=', $model->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, string $category): RedirectResponse
    {
        $this->categoryService->update($category, new UpdateCategoryData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            parentId: $request->validated('parent_id'),
            isActive: (bool) $request->validated('is_active', true),
            position: (int) $request->validated('position', 0),
        ));

        return redirect()->route('admin.catalog.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(string $category): RedirectResponse
    {
        $this->categoryService->delete($category);

        return redirect()->route('admin.catalog.categories.index')->with('status', 'Category deleted.');
    }

    public function reorder(ReorderCategoryRequest $request, string $category): RedirectResponse
    {
        $this->categoryService->reorder($category, (int) $request->validated('position'));

        return redirect()->route('admin.catalog.categories.index')->with('status', 'Category order updated.');
    }
}
