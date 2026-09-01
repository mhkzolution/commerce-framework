<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Controllers\Storefront;

use Commerce\Cms\DTO\StorefrontBlogFilters;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Commerce\Cms\Services\StorefrontBlogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PostController extends Controller
{
    public function __construct(
        private readonly StorefrontBlogService $blogService,
        private readonly CmsStructuredDataBuilder $structuredData,
    ) {}

    public function index(Request $request): View
    {
        $filters = StorefrontBlogFilters::fromRequest($request);

        return $this->archive(
            $filters,
            __('cms::blog.title'),
            $this->structuredData->collectionPage(
                __('cms::blog.title'),
                route('storefront.cms.posts.index'),
                __('cms::blog.description'),
            ),
        );
    }

    public function show(string $slug): View
    {
        $post = $this->blogService->publishedQuery()
            ->with(['category', 'tags', 'author'])
            ->where('slug', $slug)
            ->firstOrFail();

        $seo = $this->blogService->seoMeta($post);

        return view('cms::storefront.posts.show', [
            'post' => $post,
            'seo' => $seo,
            'formatted' => $this->blogService->formattedContent($post),
            'relatedPosts' => $this->blogService->relatedPosts($post),
            'recentPosts' => $this->blogService->recentPosts(5, $post),
            'popularTags' => $this->blogService->popularTags(),
            'blogService' => $this->blogService,
            'structuredData' => $this->structuredData->blogPosting($post, $this->blogService),
        ]);
    }

    public function preview(Post $post): View
    {
        $post->load(['category', 'tags', 'author']);

        $seo = $this->blogService->seoMeta($post);
        $seo['robots'] = 'noindex,nofollow';

        if (! $post->isPublished()) {
            $seo['canonical'] = null;
        }

        return view('cms::storefront.posts.show', [
            'post' => $post,
            'seo' => $seo,
            'formatted' => $this->blogService->formattedContent($post),
            'relatedPosts' => $this->blogService->relatedPosts($post),
            'recentPosts' => $this->blogService->recentPosts(5, $post),
            'popularTags' => $this->blogService->popularTags(),
            'blogService' => $this->blogService,
            'structuredData' => null,
            'isPreview' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $structuredData
     */
    public function archive(StorefrontBlogFilters $filters, string $archiveTitle, array $structuredData): View
    {
        $posts = $this->blogService->paginate($filters);
        $featured = $filters->category || $filters->tag || $filters->authorUuid
            ? null
            : $this->blogService->featuredPost();

        if ($featured !== null) {
            $posts->setCollection(
                $posts->getCollection()->reject(static fn ($post) => $post->id === $featured->id)->values(),
            );
        }

        return view('cms::storefront.posts.index', [
            'filters' => $filters,
            'posts' => $posts,
            'featured' => $featured,
            'categories' => $this->blogService->categories(),
            'popularTags' => $this->blogService->popularTags(),
            'recentPosts' => $this->blogService->recentPosts(),
            'blogService' => $this->blogService,
            'archiveTitle' => $archiveTitle,
            'structuredData' => $structuredData,
        ]);
    }
}
