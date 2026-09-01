<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\DTO\StorefrontBlogFilters;
use Commerce\Cms\Services\CategoryService;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories,
        private readonly PostController $posts,
        private readonly CmsStructuredDataBuilder $structuredData,
    ) {}

    public function show(Request $request, string $slug): View
    {
        $category = $this->categories->findActiveBySlug($slug);
        abort_if($category === null, 404);

        $filters = StorefrontBlogFilters::fromRequest($request)->withCategory((string) $category->slug);

        return $this->posts->archive(
            $filters,
            $category->name,
            $this->structuredData->categoryPage($category),
        );
    }
}
