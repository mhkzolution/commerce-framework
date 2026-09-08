<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Catalog\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

final class ShopListingFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_q_wins_and_filterable_attribute_params_are_canonicalized(): void
    {
        Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
        ]);
        Attribute::query()->create([
            'code' => 'finish',
            'name' => 'Finish',
            'type' => 'select',
            'is_filterable' => false,
        ]);

        $filters = ShopListingFilters::fromRequest(Request::create('/shop', 'GET', [
            'q' => 'tee',
            'search' => 'ignored',
            'material' => 'cotton',
            'finish' => 'matte',
            'category' => 'shirts',
            'brand' => 'acme',
        ]));

        $this->assertSame('tee', $filters->q);
        $this->assertSame('tee', $filters->search);
        $this->assertSame(['material' => 'cotton'], $filters->attributes);
        $this->assertSame([
            'q' => 'tee',
            'category' => 'shirts',
            'brand' => 'acme',
            'material' => 'cotton',
        ], $filters->toQueryArray());
    }

    public function test_legacy_search_fills_empty_q_and_is_emitted_as_q(): void
    {
        $filters = ShopListingFilters::fromRequest(Request::create('/shop', 'GET', [
            'q' => '  ',
            'search' => 'harbor mug',
        ]));

        $this->assertSame('harbor mug', $filters->q);
        $this->assertSame('harbor mug', $filters->search);
        $this->assertSame(['q' => 'harbor mug'], $filters->toQueryArray());
    }

    public function test_legacy_color_is_copied_to_attributes_and_arrays_are_ignored(): void
    {
        Attribute::query()->create([
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
        ]);
        Attribute::query()->create([
            'code' => 'material',
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
        ]);

        $filters = ShopListingFilters::fromRequest(Request::create('/shop', 'GET', [
            'color' => 'red',
            'material' => ['cotton', 'linen'],
        ]));

        $this->assertSame('red', $filters->color);
        $this->assertSame(['color' => 'red'], $filters->attributes);
        $this->assertSame(['color' => 'red'], $filters->toQueryArray());
        $this->assertTrue($filters->hasListingConstraints());
    }
}
