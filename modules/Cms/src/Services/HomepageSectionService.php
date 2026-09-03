<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Commerce\Cms\Models\HomepageSection;
use Commerce\Core\Base\BaseService;
use Illuminate\Support\Collection;

final class HomepageSectionService extends BaseService
{
    /**
     * @return Collection<int, HomepageSection>
     */
    public function ensureDefaults(): Collection
    {
        foreach (HomepageSection::defaultBlueprint() as $row) {
            HomepageSection::query()->firstOrCreate(
                ['key' => $row['key']],
                $row,
            );
        }

        return HomepageSection::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @param  list<array{uuid: string, layout: string, sort_order: int, is_active: bool, columns?: int|null}>  $sections
     */
    public function updateMany(array $sections): void
    {
        foreach ($sections as $row) {
            $section = HomepageSection::query()->where('uuid', $row['uuid'])->first();
            if ($section === null) {
                continue;
            }

            $settings = $section->settings ?? [];
            if (array_key_exists('columns', $row) && $row['columns'] !== null) {
                $settings['columns'] = max(1, min(4, (int) $row['columns']));
            }

            $section->update([
                'layout' => $row['layout'],
                'sort_order' => $row['sort_order'],
                'is_active' => $row['is_active'],
                'settings' => $settings,
            ]);
        }
    }
}
