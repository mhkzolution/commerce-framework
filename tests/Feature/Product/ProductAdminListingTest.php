<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductAdminListingTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_view_product_listing(): void
    {
        $variant = $this->createPurchasableProduct(price: 2599, sku: 'LIST-SKU-001');

        $this->actingAs(User::query()->first())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Products', false)
            ->assertSee('Export CSV', false)
            ->assertSee('Import CSV', false)
            ->assertSee('New product', false)
            ->assertSee($variant->product->name, false)
            ->assertSee('LIST-SKU-001', false)
            ->assertSee('25.99', false)
            ->assertSee('name="uuids[]"', false)
            ->assertSee('Delete selected', false);
    }

    public function test_admin_can_bulk_delete_products(): void
    {
        $keep = $this->createPurchasableProduct(sku: 'KEEP-001')->product;
        $first = $this->createPurchasableProduct(sku: 'DROP-001')->product;
        $second = $this->createPurchasableProduct(sku: 'DROP-002')->product;

        $this->actingAs(User::query()->first())
            ->from(route('admin.products.index', ['search' => 'DROP', 'status' => 'published']))
            ->post(route('admin.products.bulk-destroy'), [
                'uuids' => [$first->uuid, $second->uuid],
                'search' => 'DROP',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.products.index', ['search' => 'DROP', 'status' => 'published']))
            ->assertSessionHas('status', '2 products deleted.');

        $this->assertSoftDeleted('products', ['uuid' => $first->uuid]);
        $this->assertSoftDeleted('products', ['uuid' => $second->uuid]);
        $this->assertDatabaseHas('products', [
            'uuid' => $keep->uuid,
            'deleted_at' => null,
        ]);
        $this->assertNull(Product::query()->where('uuid', $first->uuid)->first());
    }

    public function test_bulk_delete_requires_selected_products(): void
    {
        $this->actingAs(User::query()->first())
            ->from(route('admin.products.index'))
            ->post(route('admin.products.bulk-destroy'), [
                'uuids' => [],
            ])
            ->assertSessionHasErrors('uuids');
    }

    public function test_user_without_delete_permission_cannot_bulk_delete_products(): void
    {
        $product = $this->createPurchasableProduct(sku: 'NO-DEL-001')->product;

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
            ->post(route('admin.products.bulk-destroy'), [
                'uuids' => [$product->uuid],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'uuid' => $product->uuid,
            'deleted_at' => null,
        ]);
    }
}
