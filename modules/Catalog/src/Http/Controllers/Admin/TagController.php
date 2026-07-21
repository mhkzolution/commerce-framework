<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\TagServiceInterface;
use Commerce\Catalog\DTO\CreateTagData;
use Commerce\Catalog\Http\Requests\StoreTagRequest;
use Commerce\Catalog\Services\TagQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TagController extends Controller
{
    public function __construct(
        private readonly TagQueryService $queryService,
        private readonly TagServiceInterface $tagService,
    ) {}

    public function index(): View
    {
        return view('catalog::admin.tags.index', [
            'tags' => $this->queryService->paginate(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $this->tagService->create(new CreateTagData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
        ));

        return redirect()->route('admin.catalog.tags.index')->with('status', 'Tag created.');
    }

    public function destroy(string $tag): RedirectResponse
    {
        $this->tagService->delete($tag);

        return redirect()->route('admin.catalog.tags.index')->with('status', 'Tag deleted.');
    }
}
