<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Carbon\CarbonImmutable;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Core\Models\SearchDocument;
use Commerce\Core\Search\DatabaseSearchIndex;
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
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
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

    public function test_label_change_eager_loads_index_relations_for_multiple_products(): void
    {
        $color = Attribute::query()->create([
            'code' => 'color-nplus',
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

        foreach (['NPLUS-A', 'NPLUS-B'] as $sku) {
            $product = $this->createPurchasableProduct(sku: $sku)->product;
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
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(AttributeValueService::class)->update($red->uuid, 'Crimson Red');

        $sql = array_column(DB::getQueryLog(), 'query');
        $attributeValueSelects = collect($sql)->filter(
            static fn (string $query): bool => preg_match(
                '/^select \* from ["`](?:product_)?attribute_values["`].* in \(/',
                $query,
            ) === 1,
        );
        $this->assertLessThanOrEqual(2, $attributeValueSelects->count());

        $productUuids = Product::query()
            ->whereIn(
                'id',
                ProductAttributeValue::query()
                    ->where('attribute_value_id', $red->id)
                    ->pluck('product_id'),
            )
            ->pluck('uuid');

        foreach ($productUuids as $uuid) {
            $document = SearchDocument::query()
                ->where('index_name', ProductSearchIndexer::INDEX)
                ->where('document_id', $uuid)
                ->firstOrFail();
            $this->assertContains(
                ['code' => $red->fresh()->code, 'label' => 'Crimson Red'],
                $document->payload['attributes'],
            );
        }
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

    public function test_rebuild_abort_keeps_documents_indexed_before_the_exception(): void
    {
        $first = $this->createPurchasableProduct(sku: 'REBUILD-KEEP')->product;
        $second = $this->createPurchasableProduct(sku: 'REBUILD-SKIP')->product;
        app(ProductSearchIndexer::class)->index($first);
        app(ProductSearchIndexer::class)->index($second);
        $this->staleDocument('stale-abort');

        $this->bindThrowAfterFirstIndex();

        try {
            app(ProductSearchIndexer::class)->rebuild();
            $this->fail('Expected rebuild to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('rebuild aborted', $exception->getMessage());
        }

        $this->assertDatabaseMissing('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => 'stale-abort',
        ]);
        $this->assertDatabaseHas('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $first->uuid,
        ]);
        $this->assertDatabaseMissing('search_documents', [
            'index_name' => ProductSearchIndexer::INDEX,
            'document_id' => $second->uuid,
        ]);
    }

    public function test_reindex_command_exits_nonzero_when_rebuild_throws(): void
    {
        $this->createPurchasableProduct(sku: 'CLI-KEEP');
        $this->createPurchasableProduct(sku: 'CLI-SKIP');
        $this->bindThrowAfterFirstIndex();

        $output = new BufferedOutput;
        $exitCode = app(Kernel::class)->handle(
            new ArrayInput(['command' => 'product:reindex']),
            $output,
        );
        $consoleOutput = $output->fetch();

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString('rebuild aborted', $consoleOutput);
        $this->assertStringNotContainsString('Indexed 2 products.', $consoleOutput);
    }

    public function test_admin_rebuild_does_not_flash_success_when_rebuild_throws(): void
    {
        $this->createPurchasableProduct(sku: 'HTTP-KEEP');
        $this->createPurchasableProduct(sku: 'HTTP-SKIP');
        $this->bindThrowAfterFirstIndex();

        $this->withoutExceptionHandling();

        try {
            $this->actingAs(User::query()->firstOrFail())
                ->post(route('admin.products.settings.reindex'));
            $this->fail('Expected rebuild to throw.');
        } catch (RuntimeException) {
            // uncaught is the failure response
        }

        $this->assertNull(session('status'));
    }

    private function bindThrowAfterFirstIndex(): void
    {
        $this->app->forgetInstance(ProductSearchIndexer::class);
        $this->app->bind(
            SearchIndexInterface::class,
            static fn (): SearchIndexInterface => new ThrowsAfterFirstIndex(new DatabaseSearchIndex),
        );
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

final class ThrowsAfterFirstIndex implements SearchIndexInterface
{
    public function __construct(
        private readonly SearchIndexInterface $inner,
        private int $indexCalls = 0,
    ) {}

    public function index(string $index, string $id, array $document): void
    {
        $this->indexCalls++;

        if ($this->indexCalls > 1) {
            throw new RuntimeException('rebuild aborted');
        }

        $this->inner->index($index, $id, $document);
    }

    public function delete(string $index, string $id): void
    {
        $this->inner->delete($index, $id);
    }

    public function flush(string $index): void
    {
        $this->inner->flush($index);
    }
}
