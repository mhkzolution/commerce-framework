<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $item = Post::query()->create($this->validated($request));

        return redirect()->route('admin.cms.posts.edit', $item)->with('status', 'Post created.');
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
        $post->update($this->validated($request, $post->uuid));

        return redirect()->route('admin.cms.posts.edit', $post)->with('status', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.cms.posts.index')->with('status', 'Post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?string $uuid = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_posts', 'slug')->ignore($uuid, 'uuid')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_keys(config('cms.statuses', [])))],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['title']);

        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
