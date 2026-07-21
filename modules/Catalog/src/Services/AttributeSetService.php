<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\AttributeSetServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeSetData;
use Commerce\Catalog\DTO\UpdateAttributeSetData;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Illuminate\Support\Str;

final class AttributeSetService extends BaseService implements AttributeSetServiceInterface
{
    public function create(CreateAttributeSetData $data): AttributeSet
    {
        $set = AttributeSet::query()->create([
            'code' => Str::slug($data->code, '_'),
            'name' => $data->name,
        ]);

        $this->syncAttributes($set, $data->attributeIds);

        return $set->load('attributes');
    }

    public function update(string $uuid, UpdateAttributeSetData $data): AttributeSet
    {
        $set = $this->findOrFail($uuid);

        $set->update([
            'code' => Str::slug($data->code, '_'),
            'name' => $data->name,
        ]);

        $this->syncAttributes($set, $data->attributeIds);

        return $set->fresh()->load('attributes');
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, AttributeSet>
     */
    public function paginate(int $perPage = 25)
    {
        return AttributeSet::query()->withCount('attributes')->orderBy('name')->paginate($perPage);
    }

    private function findOrFail(string $uuid): AttributeSet
    {
        $set = AttributeSet::query()->where('uuid', $uuid)->first();

        if ($set === null) {
            throw new EntityNotFoundException("Attribute set [{$uuid}] not found.");
        }

        return $set;
    }

    /**
     * @param  list<int>  $attributeIds
     */
    private function syncAttributes(AttributeSet $set, array $attributeIds): void
    {
        $sync = [];

        foreach (array_values($attributeIds) as $position => $attributeId) {
            $sync[$attributeId] = [
                'position' => $position,
                'is_required' => false,
            ];
        }

        $set->attributes()->sync($sync);
    }
}
