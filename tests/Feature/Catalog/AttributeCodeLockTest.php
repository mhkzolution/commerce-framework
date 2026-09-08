<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\Models\Attribute;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttributeCodeLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
    }

    public function test_create_slugs_code(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.attributes.store'), [
                'code' => 'Color',
                'name' => 'Color',
                'type' => 'text',
            ])
            ->assertRedirect(route('admin.catalog.attributes.index'));

        $this->assertDatabaseHas('attributes', [
            'code' => 'color',
            'name' => 'Color',
        ]);
    }

    public function test_update_rejects_a_different_identity_and_does_not_partial_update(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'shoe_size',
            'name' => 'Shoe size',
            'type' => 'text',
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.catalog.attributes.update', $attribute->uuid), [
                'code' => 'footwear_size',
                'name' => 'Renamed',
                'type' => 'text',
            ])
            ->assertInvalid('code');

        $fresh = $attribute->fresh();
        $this->assertSame('shoe_size', $fresh->code);
        $this->assertSame('Shoe size', $fresh->name);
    }

    public function test_update_accepts_normalized_same_identity(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'shoe_size',
            'name' => 'Shoe size',
            'type' => 'text',
        ]);

        foreach (['shoe_size', 'Shoe Size', 'shoe-size'] as $postedCode) {
            $this->actingAs(User::query()->first())
                ->put(route('admin.catalog.attributes.update', $attribute->uuid), [
                    'code' => $postedCode,
                    'name' => 'Footwear size',
                    'type' => 'text',
                ])
                ->assertRedirect(route('admin.catalog.attributes.index'));

            $fresh = $attribute->fresh();
            $this->assertSame('shoe_size', $fresh->code);
            $this->assertSame('Footwear size', $fresh->name);
        }
    }
}
