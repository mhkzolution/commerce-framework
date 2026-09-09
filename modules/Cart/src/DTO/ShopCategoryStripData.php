<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class ShopCategoryStripData extends DataTransferObject
{
    /**
     * @param  list<HomepageNavigationData>  $items
     */
    public function __construct(
        public array $items,
        public bool $showBack = false,
    ) {}

    /**
     * @param  list<HomepageNavigationData>  $tree
     */
    public static function for(?string $selectedSlug, array $tree): self
    {
        $selectedSlug = is_string($selectedSlug) ? trim($selectedSlug) : '';
        if ($selectedSlug === '') {
            return new self($tree);
        }

        foreach ($tree as $parent) {
            if ($parent->slug === $selectedSlug || self::findInTree($parent->children, $selectedSlug) !== null) {
                return new self($parent->children, showBack: true);
            }
        }

        return new self($tree);
    }

    /**
     * @param  list<HomepageNavigationData>  $nodes
     */
    public static function findInTree(array $nodes, string $slug): ?HomepageNavigationData
    {
        foreach ($nodes as $node) {
            if ($node->slug === $slug) {
                return $node;
            }

            $hit = self::findInTree($node->children, $slug);
            if ($hit instanceof HomepageNavigationData) {
                return $hit;
            }
        }

        return null;
    }
}
