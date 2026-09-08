<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Services\AttributeService;
use Commerce\Core\Exceptions\DomainException;
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

    public function test_model_rejects_dirty_code_after_persistence(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'text',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Attribute code is immutable.');

        $attribute->update(['code' => 'fabric']);
    }

    public function test_create_is_not_blocked_by_immutability(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'brand_fit',
            'name' => 'Brand fit',
            'type' => 'text',
        ]);

        $this->assertSame('brand_fit', $attribute->code);
    }

    public function test_service_update_preserves_code(): void
    {
        $attribute = Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'text',
        ]);

        $updated = app(AttributeService::class)->update($attribute->uuid, new UpdateAttributeData(
            name: 'Fabric',
            type: 'text',
        ));

        $this->assertSame('material', $updated->code);
        $this->assertSame('Fabric', $updated->name);
    }
}
