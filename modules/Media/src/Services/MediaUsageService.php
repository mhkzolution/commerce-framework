<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MediaUsageService
{
    /**
     * @return list<array{key: string, label: string, title: string}>
     */
    public function forUuid(string $uuid): array
    {
        $usages = [];

        foreach ($this->sources() as $source) {
            if (! Schema::hasTable($source['table']) || ! Schema::hasColumn($source['table'], $source['column'])) {
                continue;
            }

            $query = DB::table($source['table'])->where($source['column'], $uuid);

            if (isset($source['owner_table'], $source['foreign_key'], $source['owner_key'])
                && Schema::hasTable($source['owner_table'])
            ) {
                $query->join(
                    $source['owner_table'],
                    $source['table'].'.'.$source['foreign_key'],
                    '=',
                    $source['owner_table'].'.'.$source['owner_key'],
                );
                $titleColumn = $source['owner_table'].'.'.($source['title'] ?? 'name');
                $rows = $query->select($titleColumn.' as title')->limit(25)->get();
            } else {
                $title = $source['title'] ?? null;
                $select = $title && Schema::hasColumn($source['table'], $title)
                    ? [$title.' as title']
                    : [DB::raw("'".$source['label']."' as title")];
                $rows = $query->select($select)->limit(25)->get();
            }

            foreach ($rows as $row) {
                $title = trim((string) ($row->title ?? ''));
                $usages[] = [
                    'key' => $source['key'],
                    'label' => $source['label'],
                    'title' => $title !== '' ? $title : $source['label'],
                ];
            }
        }

        return $usages;
    }

    /**
     * @param  list<string>  $uuids
     * @return array<string, list<array{key: string, label: string, title: string}>>
     */
    public function forUuids(array $uuids): array
    {
        $map = [];

        foreach (array_values(array_unique($uuids)) as $uuid) {
            $map[$uuid] = $this->forUuid($uuid);
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sources(): array
    {
        $sources = config('media.usage_sources', []);

        return is_array($sources) ? array_values(array_filter($sources, 'is_array')) : [];
    }
}
