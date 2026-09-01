<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class SupplierAdminTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_user_can_update_supplier(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Old Vendor',
            'email' => 'old@example.com',
        ]);

        $this->actingAs(User::query()->first())
            ->put(route('admin.inventory.suppliers.update', $supplier), [
                'name' => 'New Vendor',
                'email' => 'new@example.com',
                'phone' => '0812345678',
            ])
            ->assertRedirect(route('admin.inventory.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'New Vendor',
            'email' => 'new@example.com',
            'phone' => '0812345678',
        ]);
    }

    public function test_user_can_delete_supplier(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Disposable Vendor']);

        $this->actingAs(User::query()->first())
            ->delete(route('admin.inventory.suppliers.destroy', $supplier))
            ->assertRedirect(route('admin.inventory.suppliers.index'));

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_user_can_view_supplier_report(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Report Vendor']);

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Report Vendor')
            ->assertSee('Total POs');
    }

    public function test_user_can_export_supplier_purchase_orders_csv(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Export Vendor']);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.suppliers.export', $supplier));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));
    }

    public function test_supplier_report_filters_by_date_range(): void
    {
        $supplier = Supplier::query()->create(['name' => 'Range Vendor']);
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'SUP-RANGE-001');

        $recent = app(PurchaseOrderService::class)->create(
            reference: 'PO-RANGE-RECENT',
            lines: [['sku' => $variant->sku, 'quantity' => 1]],
            supplierId: $supplier->id,
        );
        $recent->update(['created_at' => now()]);

        $old = app(PurchaseOrderService::class)->create(
            reference: 'PO-RANGE-OLD',
            lines: [['sku' => $variant->sku, 'quantity' => 1]],
            supplierId: $supplier->id,
        );
        PurchaseOrder::query()->whereKey($old->id)->update(['created_at' => now()->subMonths(3)]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.suppliers.show', ['supplier' => $supplier, 'range' => '30d']))
            ->assertOk()
            ->assertSee('PO-RANGE-RECENT')
            ->assertDontSee('PO-RANGE-OLD');
    }
}
