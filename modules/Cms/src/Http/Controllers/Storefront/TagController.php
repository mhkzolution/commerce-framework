<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\DTO\StorefrontBlogFilters;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Commerce\Cms\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tags,
        private readonly PostController $posts,
        private readonly CmsStructuredDataBuilder $structuredData,
    ) {}

    public function show(Request $request, string $slug): View
    {
        $tag = $this->tags->findBySlug($slug);
        abort_if($tag === null, 404);

        $filters = StorefrontBlogFilters::fromRequest($request)->withTag((string) $tag->slug);

        return $this->posts->archive(
            $filters,
            $tag->name,
            $this->structuredData->tagPage($tag),
        );
    }
}
