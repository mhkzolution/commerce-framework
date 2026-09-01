<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\DTO\StorefrontBlogFilters;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Commerce\Cms\Services\StorefrontBlogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AuthorController extends Controller
{
    public function __construct(
        private readonly StorefrontBlogService $blogService,
        private readonly PostController $posts,
        private readonly CmsStructuredDataBuilder $structuredData,
    ) {}

    public function show(Request $request, string $author): View
    {
        $user = $this->blogService->findAuthor($author);
        abort_if($user === null, 404);

        $filters = StorefrontBlogFilters::fromRequest($request)->withAuthor($user->uuid);
        $url = route('storefront.cms.authors.show', $user->uuid);

        return $this->posts->archive(
            $filters,
            $user->name,
            $this->structuredData->profilePage($user, $url),
        );
    }
}
