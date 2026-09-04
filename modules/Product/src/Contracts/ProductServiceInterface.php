<?php

declare(strict_types=1);

namespace Commerce\Product\Contracts;

use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\DTO\UpdateProductData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;

interface ProductServiceInterface
{
    public function create(CreateProductData $data): Product;

    public function update(string $uuid, UpdateProductData $data): Product;

    public function delete(string $uuid): void;

    /**
     * @param  list<string>  $uuids
     */
    public function deleteMany(array $uuids): int;

    public function publish(string $uuid): Product;

    public function archive(string $uuid): Product;

    public function addVariant(CreateVariantData $data): ProductVariant;

    public function deleteVariant(string $uuid): void;

    public function publishScheduled(): int;
}
