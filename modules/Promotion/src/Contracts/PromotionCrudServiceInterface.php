<?php

declare(strict_types=1);

namespace Commerce\Promotion\Contracts;

use Commerce\Promotion\DTO\UpsertPromotionData;
use Commerce\Promotion\Models\Promotion;

interface PromotionCrudServiceInterface
{
    public function create(UpsertPromotionData $data): Promotion;

    public function update(string $uuid, UpsertPromotionData $data): Promotion;

    public function delete(string $uuid): void;
}
