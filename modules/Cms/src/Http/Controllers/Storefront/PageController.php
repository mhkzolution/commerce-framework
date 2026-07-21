<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\Services\PageService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function __construct(private readonly PageService $pages) {}

    public function show(string $slug): View
    {
        $page = $this->pages->findPublishedBySlug($slug);

        abort_if($page === null, 404);

        return view('cms::storefront.page', [
            'page' => $page,
        ]);
    }
}
