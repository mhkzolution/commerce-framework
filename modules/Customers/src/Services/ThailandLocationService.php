<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

final class ThailandLocationService
{
    /** @var list<array{i: int, t: string, e: string, d: list<array<string, mixed>>}>|null */
    private ?array $tree = null;

    /**
     * @return list<array{id: int, name_th: string, name_en: string}>
     */
    public function provinces(): array
    {
        return array_map(
            static fn (array $province): array => [
                'id' => (int) $province['i'],
                'name_th' => (string) $province['t'],
                'name_en' => (string) $province['e'],
            ],
            $this->tree(),
        );
    }

    /**
     * @return list<array{id: int, name_th: string, name_en: string}>
     */
    public function districts(int $provinceId): array
    {
        foreach ($this->tree() as $province) {
            if ((int) $province['i'] !== $provinceId) {
                continue;
            }

            return array_map(
                static fn (array $district): array => [
                    'id' => (int) $district['i'],
                    'name_th' => (string) $district['t'],
                    'name_en' => (string) $district['e'],
                ],
                $province['d'] ?? [],
            );
        }

        return [];
    }

    /**
     * @return list<array{id: int, name_th: string, name_en: string, postal_code: string}>
     */
    public function subdistricts(int $districtId): array
    {
        foreach ($this->tree() as $province) {
            foreach ($province['d'] ?? [] as $district) {
                if ((int) $district['i'] !== $districtId) {
                    continue;
                }

                return array_map(
                    static fn (array $subdistrict): array => [
                        'id' => (int) $subdistrict['i'],
                        'name_th' => (string) $subdistrict['t'],
                        'name_en' => (string) $subdistrict['e'],
                        'postal_code' => (string) ($subdistrict['z'] ?? ''),
                    ],
                    $district['s'] ?? [],
                );
            }
        }

        return [];
    }

    /**
     * @return list<array{i: int, t: string, e: string, d: list<array<string, mixed>>}>
     */
    private function tree(): array
    {
        if ($this->tree !== null) {
            return $this->tree;
        }

        $path = dirname(__DIR__, 2).'/resources/data/thailand-locations.json.gz';
        $json = gzdecode((string) file_get_contents($path));

        if (! is_string($json) || $json === '') {
            $this->tree = [];

            return $this->tree;
        }

        $decoded = json_decode($json, true);
        $this->tree = is_array($decoded) ? $decoded : [];

        return $this->tree;
    }
}
