<?php

declare(strict_types=1);

namespace Commerce\Wishlist\DTO;

final readonly class WishlistItemViewData
{
    public function __construct(
        public string $productId,
        public ?string $variantId,
        public string $name,
        public string $slug,
        public ?string $imageUrl,
        public int|float $price,
        public string $currency,
        public ?string $variantLabel,
        public string $url,
        public ?string $imageSrcset = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'name' => $this->name,
            'slug' => $this->slug,
            'image_url' => $this->imageUrl,
            'image_srcset' => $this->imageSrcset,
            'price' => $this->price,
            'currency' => $this->currency,
            'variant_label' => $this->variantLabel,
            'url' => $this->url,
        ];
    }
}
