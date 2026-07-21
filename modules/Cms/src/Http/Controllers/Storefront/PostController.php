<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\Models\Post;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        return view('cms::storefront.posts.index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('cms::storefront.posts.show', [
            'post' => $post,
        ]);
    }
}
