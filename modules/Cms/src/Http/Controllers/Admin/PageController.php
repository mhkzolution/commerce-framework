<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]);

        $item = Page::query()->create(array_filter($data));

        return redirect()->route('admin.cms.pages.edit', $item)->with('status', 'Created.');
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
        $page->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.cms.pages.edit', $page)->with('status', 'Saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.cms.pages.index')->with('status', 'Deleted.');
    }
}