<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Core\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttributeValueMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_backfills_option_strings_into_attribute_values(): void
    {
        $migrationPath = base_path(
            'modules/Catalog/database/migrations/2026_09_07_200000_create_attribute_values_table.php',
        );

        $this->assertFileExists($migrationPath);

        $migration = require $migrationPath;
        $migration->down();

        $attribute = Attribute::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => ['Red', 'Blue', 'Red'],
        ]);

        $migration->up();

        $codes = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->orderBy('position')
            ->pluck('code')
            ->all();

        $this->assertSame(['red', 'blue', 'red-2'], $codes);
    }

    public function test_migration_backfill_does_not_duplicate_rows_when_re_run(): void
    {
        $migrationPath = base_path(
            'modules/Catalog/database/migrations/2026_09_07_200000_create_attribute_values_table.php',
        );

        $this->assertFileExists($migrationPath);

        $migration = require $migrationPath;
        $migration->down();

        $attribute = Attribute::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => ['Red', 'Blue', 'Red'],
        ]);

        $migration->up();
        $migration->up();

        $values = AttributeValue::query()
            ->where('attribute_id', $attribute->id)
            ->orderBy('position')
            ->get();

        $this->assertCount(3, $values);
        $this->assertSame(['red', 'blue', 'red-2'], $values->pluck('code')->all());
        $this->assertSame(['Red', 'Blue', 'Red'], $values->pluck('label')->all());
    }

    public function test_updating_label_does_not_change_code(): void
    {
        $attribute = Attribute::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);

        $service = app(AttributeValueService::class);
        $code = $service->allocateCode($attribute->id, 'Burgundy');

        $value = AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => $code,
            'label' => 'Burgundy',
            'position' => 0,
        ]);

        $this->assertSame('burgundy', $value->code);

        $service->update($value->uuid, 'Dark Burgundy', 0);

        $fresh = $value->fresh();
        $this->assertSame('burgundy', $fresh->code);
        $this->assertSame('Dark Burgundy', $fresh->label);

        $this->expectException(DomainException::class);
        $fresh->update(['code' => 'dark_burgundy']);
    }
}
