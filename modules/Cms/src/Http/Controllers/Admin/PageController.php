<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PageController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $item = Page::query()->create($data);

        return redirect()->route('admin.cms.pages.edit', $item)->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('cms::admin.pages.edit', [
            'item' => $page,
            'statuses' => config('cms.statuses', []),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page->uuid));

        return redirect()->route('admin.cms.pages.edit', $page)->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.cms.pages.index')->with('status', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $uuid = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($uuid, 'uuid')],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_keys(config('cms.statuses', [])))],
        ]);

        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['title']);

        return $data;
    }
}
