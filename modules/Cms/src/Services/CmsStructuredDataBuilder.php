<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\Models\Category;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\Tag;
use Commerce\Iam\Models\User;

final class CmsStructuredDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function blogPosting(Post $post, StorefrontBlogService $blog): array
    {
        $url = route('storefront.cms.posts.show', $post->slug);
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blog->seoTitle($post),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
        ];

        $description = $blog->seoDescription($post);
        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($post->published_at !== null) {
            $data['datePublished'] = $post->published_at->toIso8601String();
        }

        if ($post->updated_at !== null) {
            $data['dateModified'] = $post->updated_at->toIso8601String();
        }

        $image = $blog->featuredImageUrl($post, 'large');
        if ($image !== null) {
            $data['image'] = [$image];
        }

        $section = $blog->categoryLabel($post);
        if ($section !== null) {
            $data['articleSection'] = $section;
        }

        $author = $blog->authorName($post);
        if ($author !== null) {
            $data['author'] = [
                '@type' => 'Person',
                'name' => $author,
            ];
        }

        $minutes = $blog->readingTime($post);
        $data['timeRequired'] = 'PT'.$minutes.'M';
        $data['wordCount'] = str_word_count(strip_tags((string) $post->content));

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function collectionPage(string $name, string $url, ?string $description = null): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'url' => $url,
        ];

        if ($description !== null && $description !== '') {
            $data['description'] = $description;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function profilePage(User $author, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'name' => $author->name,
            'url' => $url,
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $author->name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function webPage(Page $page): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $page->title,
            'url' => route('storefront.cms.pages.show', $page->slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function categoryPage(Category $category): array
    {
        return $this->collectionPage(
            $category->name,
            route('storefront.cms.categories.show', $category->slug),
            $category->description,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function tagPage(Tag $tag): array
    {
        return $this->collectionPage(
            $tag->name,
            route('storefront.cms.tags.show', $tag->slug),
            $tag->description,
        );
    }
}
