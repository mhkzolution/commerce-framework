<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function index(): View
    {
        return view('cms::admin.posts.index', [
            'items' => Post::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.posts.create', [
            'statuses' => config('cms.statuses', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $item = Post::query()->create($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.cms.posts.edit', $item)->with('status', 'Created.');
    }

    public function edit(Post $post): View
    {
        return view('cms::admin.posts.edit', [
            'item' => $post,
            'statuses' => config('cms.statuses', []),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $post->update($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('admin.cms.posts.edit', $post)->with('status', 'Saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.cms.posts.index')->with('status', 'Deleted.');
    }
}