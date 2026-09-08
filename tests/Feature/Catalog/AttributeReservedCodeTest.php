<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Attribute;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttributeReservedCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
    }

    public function test_reserved_code_cannot_be_used_when_creating_an_attribute(): void
    {
        $this->actingAs(User::query()->first())
            ->postJson(route('admin.catalog.attributes.store'), [
                'code' => 'brand',
                'name' => 'Brand',
                'type' => 'text',
            ])
            ->assertUnprocessable()
            ->assertInvalid('code');

        $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
    }

    public function test_reserved_code_cannot_be_used_when_updating_an_attribute(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'text',
        ]);

        foreach (['q', 'page'] as $reservedCode) {
            $this->actingAs(User::query()->first())
                ->putJson(route('admin.catalog.attributes.update', $attribute->uuid), [
                    'code' => $reservedCode,
                    'name' => 'Material',
                    'type' => 'text',
                ])
                ->assertUnprocessable()
                ->assertInvalid('code');
        }

        $this->assertSame('material', $attribute->fresh()->code);
    }
}
