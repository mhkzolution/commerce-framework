<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\CreatePageData;
use Commerce\Cms\DTO\UpdatePageData;
use Commerce\Cms\Http\Requests\StorePageRequest;
use Commerce\Cms\Http\Requests\UpdatePageRequest;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Services\PageService;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pages,
        private readonly SeoServiceInterface $seoService,
    ) {}

    public function index(): View
    {
        return view('cms::admin.pages.index', [
            'items' => Page::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.pages.create', [
            'statuses' => config('cms.statuses', []),
        ]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $item = $this->pages->create(new CreatePageData(
            title: $request->validated('title'),
            slug: $request->validated('slug'),
            content: $request->validated('content'),
            status: $request->validated('status'),
            publishedAt: $request->validated('published_at'),
            unpublishAt: $request->validated('unpublish_at'),
            seo: $request->validated('seo'),
        ));

        return redirect()->route('admin.cms.pages.edit', $item)->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('cms::admin.pages.edit', [
            'item' => $page,
            'statuses' => config('cms.statuses', []),
            'seo' => $this->seoService->getForEntity(Page::SEO_ENTITY_TYPE, $page->uuid),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->pages->update($page, new UpdatePageData(
            title: $request->validated('title'),
            slug: $request->validated('slug'),
            content: $request->validated('content'),
            status: $request->validated('status'),
            publishedAt: $request->validated('published_at'),
            unpublishAt: $request->validated('unpublish_at'),
            seo: $request->validated('seo'),
        ));

        return redirect()->route('admin.cms.pages.edit', $page)->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->pages->delete($page);

        return redirect()->route('admin.cms.pages.index')->with('status', 'Page deleted.');
    }
}
