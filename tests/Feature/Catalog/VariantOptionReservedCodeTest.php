<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Services\VariantOptionPresetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VariantOptionReservedCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
    }

    public function test_reserved_code_cannot_be_used_when_creating_a_variant_option(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.catalog.variant-options.store'), [
                'code' => 'brand',
                'name' => 'Brand',
                'options' => ['Nike'],
                'position' => 0,
            ])
            ->assertInvalid('code');

        $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
    }

    public function test_slug_normalized_reserved_code_cannot_be_used_when_creating_a_variant_option(): void
    {
        foreach ([
            'Brand' => 'brand',
            'price-min' => 'price_min',
        ] as $submittedCode => $persistedCode) {
            $this->actingAs(User::query()->first())
                ->post(route('admin.catalog.variant-options.store'), [
                    'code' => $submittedCode,
                    'name' => 'Reserved option',
                    'options' => ['One'],
                    'position' => 0,
                ])
                ->assertInvalid('code');

            $this->assertDatabaseMissing('attributes', ['code' => $persistedCode]);
        }
    }

    public function test_reserved_code_cannot_be_used_when_updating_a_variant_option(): void
    {
        $option = app(VariantOptionPresetService::class)->create(
            name: 'Color',
            code: 'color',
            options: ['Red', 'Blue'],
            position: 0,
        );

        foreach (['q', 'page', 'sort'] as $reservedCode) {
            $this->actingAs(User::query()->first())
                ->put(route('admin.catalog.variant-options.update', $option->uuid), [
                    'code' => $reservedCode,
                    'name' => 'Color',
                    'options' => ['Red', 'Blue'],
                    'position' => 0,
                ])
                ->assertInvalid('code');
        }

        $this->assertSame('color', $option->fresh()->code);
    }
}
