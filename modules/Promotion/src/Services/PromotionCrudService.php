<?php

declare(strict_types=1);

namespace Commerce\Promotion\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Promotion\Contracts\PromotionCrudServiceInterface;
use Commerce\Promotion\DTO\UpsertPromotionData;
use Commerce\Promotion\Models\Promotion;

final class PromotionCrudService extends BaseService implements PromotionCrudServiceInterface
{
    public function create(UpsertPromotionData $data): Promotion
    {
        if (Promotion::query()->whereRaw('LOWER(code) = ?', [strtolower($data->code)])->exists()) {
            throw new DomainException('Promotion code already exists.');
        }

        return Promotion::query()->create([
            'code' => strtoupper($data->code),
            'name' => $data->name,
            'type' => $data->type,
            'value' => $data->value,
            'min_subtotal' => $data->minSubtotal,
            'max_uses' => $data->maxUses,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'is_active' => $data->isActive,
        ]);
    }

    public function update(string $uuid, UpsertPromotionData $data): Promotion
    {
        $promotion = $this->findOrFail($uuid);

        if (Promotion::query()->whereRaw('LOWER(code) = ?', [strtolower($data->code)])
            ->where('id', '!=', $promotion->id)->exists()) {
            throw new DomainException('Promotion code already exists.');
        }

        $promotion->update([
            'code' => strtoupper($data->code),
            'name' => $data->name,
            'type' => $data->type,
            'value' => $data->value,
            'min_subtotal' => $data->minSubtotal,
            'max_uses' => $data->maxUses,
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'is_active' => $data->isActive,
        ]);

        return $promotion->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    private function findOrFail(string $uuid): Promotion
    {
        $promotion = Promotion::query()->where('uuid', $uuid)->first();
        if ($promotion === null) {
            throw new EntityNotFoundException("Promotion [{$uuid}] not found.");
        }

        return $promotion;
    }
}
