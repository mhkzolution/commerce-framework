<?php

declare(strict_types=1);

namespace Commerce\Cms\DTO;

use Illuminate\Http\Request;

final readonly class StorefrontBlogFilters
{
    public const SORT_LATEST = 'latest';

    public const SORT_POPULAR = 'popular';

    public function __construct(
        public ?string $search = null,
        public ?string $category = null,
        public ?string $tag = null,
        public ?string $authorUuid = null,
        public string $sort = self::SORT_LATEST,
        public int $page = 1,
        public int $perPage = 12,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = $request->string('sort')->toString();

        return new self(
            search: $request->string('search')->toString() ?: null,
            category: $request->string('category')->toString() ?: null,
            tag: $request->string('tag')->toString() ?: null,
            authorUuid: $request->string('author')->toString() ?: null,
            sort: in_array($sort, [self::SORT_LATEST, self::SORT_POPULAR], true) ? $sort : self::SORT_LATEST,
            page: max(1, (int) $request->input('page', 1)),
            perPage: min(24, max(6, (int) $request->input('per_page', 12))),
        );
    }

    public function withCategory(string $slug): self
    {
        return new self(
            search: $this->search,
            category: $slug,
            tag: $this->tag,
            authorUuid: $this->authorUuid,
            sort: $this->sort,
            page: $this->page,
            perPage: $this->perPage,
        );
    }

    public function withTag(string $slug): self
    {
        return new self(
            search: $this->search,
            category: $this->category,
            tag: $slug,
            authorUuid: $this->authorUuid,
            sort: $this->sort,
            page: $this->page,
            perPage: $this->perPage,
        );
    }

    public function withAuthor(string $uuid): self
    {
        return new self(
            search: $this->search,
            category: $this->category,
            tag: $this->tag,
            authorUuid: $uuid,
            sort: $this->sort,
            page: $this->page,
            perPage: $this->perPage,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'category' => $this->category,
            'tag' => $this->tag,
            'author' => $this->authorUuid,
            'sort' => $this->sort !== self::SORT_LATEST ? $this->sort : null,
            'per_page' => $this->perPage !== 12 ? $this->perPage : null,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
