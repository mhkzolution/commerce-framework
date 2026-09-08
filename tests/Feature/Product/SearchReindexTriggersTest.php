<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Carbon\CarbonImmutable;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Core\Models\SearchDocument;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Services\ProductDiscoveryQuery;
use Commerce\Product\Services\ProductSearchIndexer;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class SearchReindexTriggersTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_label_change_reindexes_affected_products_without_changing_filter_code(): void
    {
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $red = AttributeValue::query()->create([
            'tenant_id' => $color->tenant_id,
            'attribute_id' => $color->id,
            'code' => app(AttributeValueService::class)->allocateCode($color->id, 'Red'),
            'label' => 'Red',
            'position' => 0,
        ]);
        $variant = $this->createPurchasableProduct(sku: 'LABEL-RED');
        $product = $variant->product;
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'used_for_variations' => false,
            'position' => 0,
        ]);
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'product_variant_id' => null,
            'attribute_value_id' => $red->id,
            'value' => $red->label,
        ]);
        app(ProductSearchIndexer::class)->index($product->fresh());

        app(AttributeValueService::class)->update($red->uuid, 'Crimson Red');

        $this->assertSame('red', $red->fresh()->code);
        $this->assertContains(
            $product->uuid,
            app(ProductDiscoveryQuery::class)->candidateUuids('crimson'),
        );
        $document = SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $product->uuid)
            ->firstOrFail();
        $this->assertContains(
            ['code' => 'red', 'label' => 'Crimson Red'],
            $document->payload['attributes'],
        );
        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_synonym_create_and_update_do_not_rewrite_search_documents(): void
    {
        CarbonImmutable::setTestNow('2026-09-08 08:00:00');
        $product = $this->createPurchasableProduct(sku: 'SYNONYM-DOC')->product;
        app(ProductSearchIndexer::class)->index($product);
        $document = SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $product->uuid)
            ->firstOrFail();
        $originalUpdatedAt = $document->updated_at;

        CarbonImmutable::setTestNow('2026-09-08 09:00:00');
        $synonym = SearchSynonym::query()->create([
            'from_term' => 'scarlet',
            'to_term' => 'red',
        ]);
        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));

        CarbonImmutable::setTestNow('2026-09-08 10:00:00');
        $synonym->update(['to_term' => 'crimson']);
        $this->assertTrue($originalUpdatedAt->equalTo($document->fresh()->updated_at));
    }

    public function test_settings_and_command_rebuild_flush_stale_documents(): void
    {
        $product = $this->createPurchasableProduct(sku: 'REBUILD-DOC')->product;
        app(ProductSearchIndexer::class)->index($product);
        $this->staleDocument('stale-settings');

        $this->actingAs(User::query()->firstOrFail())
            ->post(route('admin.products.settings.reindex'))
            ->assertRedirect(route('admin.products.settings.show'));

        $this->assertDatabaseMissing('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'stale-settings',
        ]);
        $this->assertDatabaseHas('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $product->uuid,
        ]);

        $this->staleDocument('stale-command');
        $this->artisan('product:reindex')->assertSuccessful();
        $this->assertDatabaseMissing('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'stale-command',
        ]);
        $this->assertDatabaseHas('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $product->uuid,
        ]);
    }

    public function test_user_with_view_permission_can_rebuild_search_index(): void
    {
        $role = Role::query()->create([
            'name' => 'Catalog Viewer',
            'code' => 'catalog-viewer',
            'is_system' => false,
        ]);
        $role->permissions()->sync(
            Permission::query()->where('name', 'product.product.view')->pluck('id'),
        );

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: 'Viewer',
            email: 'catalog-viewer@example.test',
            password: 'password',
            roleCodes: [$role->code],
        ));
        app(AuthorizationService::class)->clearCacheForUser($user->id);

        $this->actingAs($user)
            ->post(route('admin.products.settings.reindex'))
            ->assertRedirect(route('admin.products.settings.show'));
    }

    private function staleDocument(string $documentId): void
    {
        SearchDocument::query()->create([
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $documentId,
            'title' => 'Stale document',
            'body' => '',
            'payload' => [],
        ]);
    }
}
