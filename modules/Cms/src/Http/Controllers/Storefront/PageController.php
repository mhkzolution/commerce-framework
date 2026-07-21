<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\Models\Page;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('cms::storefront.page', [
            'page' => $page,
        ]);
    }
}
