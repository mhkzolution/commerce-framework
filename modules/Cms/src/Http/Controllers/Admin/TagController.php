<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\CreateTagData;
use Commerce\Cms\Http\Requests\StoreTagRequest;
use Commerce\Cms\Models\Tag;
use Commerce\Cms\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tags,
    ) {}

    public function index(): View
    {
        return view('cms::admin.tags.index', [
            'items' => Tag::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $this->tags->create(new CreateTagData(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
        ));

        return redirect()->route('admin.cms.tags.index')->with('status', 'Tag created.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->tags->delete($tag);

        return redirect()->route('admin.cms.tags.index')->with('status', 'Tag deleted.');
    }
}
