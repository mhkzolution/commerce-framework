<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\Models\Page;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Commerce\Cms\Services\PageService;
use Commerce\Cms\Support\CmsSeoSync;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pages,
        private readonly CmsSeoSync $cmsSeo,
        private readonly CmsStructuredDataBuilder $structuredData,
    ) {}

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedBySlug($slug);

        abort_if($page === null, 404);

        $seo = $this->cmsSeo->pageMeta(Page::SEO_ENTITY_TYPE, $page->uuid, $page->title);
        if ($seo['canonical'] === null && filled($page->slug)) {
            $seo['canonical'] = route('storefront.cms.pages.show', $page->slug);
        }

        return view('cms::storefront.page', [
            'page' => $page,
            'seo' => $seo,
            'structuredData' => $this->structuredData->webPage($page),
        ]);
    }
}
