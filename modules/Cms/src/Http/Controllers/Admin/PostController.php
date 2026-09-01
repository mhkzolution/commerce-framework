<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Admin;

use Commerce\Cms\DTO\CreatePostData;
use Commerce\Cms\DTO\UpdatePostData;
use Commerce\Cms\Http\Requests\StorePostRequest;
use Commerce\Cms\Http\Requests\UpdatePostRequest;
use Commerce\Cms\Models\Category;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\Tag;
use Commerce\Cms\Services\PostService;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function __construct(
        private readonly PostService $posts,
        private readonly SeoServiceInterface $seoService,
    ) {}

    public function index(): View
    {
        return view('cms::admin.posts.index', [
            'items' => Post::query()->with(['category', 'author'])->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('cms::admin.posts.create', $this->formData());
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $item = $this->posts->create($this->toCreateData($request));

        return redirect()->route('admin.cms.posts.edit', $item)->with('status', 'Post created.');
    }

    public function edit(Post $post): View
    {
        $post->load(['tags']);

        return view('cms::admin.posts.edit', [
            ...$this->formData(),
            'item' => $post,
            'seo' => $this->seoService->getForEntity(Post::SEO_ENTITY_TYPE, $post->uuid),
            'previewUrl' => URL::temporarySignedRoute(
                'storefront.cms.posts.preview',
                now()->addHours(12),
                ['post' => $post->uuid],
            ),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->posts->update($post, $this->toUpdateData($request));

        return redirect()->route('admin.cms.posts.edit', $post)->with('status', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->posts->delete($post);

        return redirect()->route('admin.cms.posts.index')->with('status', 'Post deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'statuses' => config('cms.statuses', []),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'authors' => User::query()->orderBy('name')->get(['uuid', 'name', 'email']),
        ];
    }

    private function toCreateData(StorePostRequest $request): CreatePostData
    {
        return new CreatePostData(
            title: $request->validated('title'),
            slug: $request->validated('slug'),
            excerpt: $request->validated('excerpt'),
            content: $request->validated('content'),
            status: $request->validated('status'),
            publishedAt: $request->validated('published_at'),
            unpublishAt: $request->validated('unpublish_at'),
            categoryId: $request->validated('category_id') !== null ? (int) $request->validated('category_id') : null,
            tagIds: array_map('intval', $request->validated('tag_ids') ?? []),
            authorUuid: $request->validated('author_uuid') ?: $request->user()?->uuid,
            featuredImageMediaUuid: $request->validated('featured_image_media_uuid'),
            isFeatured: $request->boolean('is_featured'),
            seo: $request->validated('seo'),
        );
    }

    private function toUpdateData(UpdatePostRequest $request): UpdatePostData
    {
        return new UpdatePostData(
            title: $request->validated('title'),
            slug: $request->validated('slug'),
            excerpt: $request->validated('excerpt'),
            content: $request->validated('content'),
            status: $request->validated('status'),
            publishedAt: $request->validated('published_at'),
            unpublishAt: $request->validated('unpublish_at'),
            categoryId: $request->validated('category_id') !== null ? (int) $request->validated('category_id') : null,
            tagIds: array_map('intval', $request->validated('tag_ids') ?? []),
            authorUuid: $request->validated('author_uuid'),
            featuredImageMediaUuid: $request->validated('featured_image_media_uuid'),
            isFeatured: $request->boolean('is_featured'),
            seo: $request->validated('seo'),
        );
    }
}
