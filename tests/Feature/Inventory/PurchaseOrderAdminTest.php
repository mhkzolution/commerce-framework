<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Mail\PurchaseOrderPdfMail;
use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Services\PurchaseOrderService;
use Commerce\Inventory\Support\PurchaseOrderMailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PurchaseOrderAdminTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_user_with_view_permission_can_list_purchase_orders(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.index'))
            ->assertOk()
            ->assertSee('Purchase orders');
    }

    public function test_user_with_manage_permission_can_create_purchase_order(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ADMIN-001');

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.store'), [
                'reference' => 'PO-ADMIN-100',
                'lines' => [
                    ['sku' => $variant->sku, 'quantity' => 4],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'reference' => 'PO-ADMIN-100',
            'status' => 'ordered',
        ]);
    }

    public function test_user_with_manage_permission_can_cancel_purchase_order(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ADMIN-002');
        $order = app(PurchaseOrderService::class)->create('PO-ADMIN-200', [
            ['sku' => $variant->sku, 'quantity' => 3],
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.cancel', $order))
            ->assertRedirect(route('admin.inventory.purchase-orders.show', $order));

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_user_with_manage_permission_can_create_purchase_order_with_supplier(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ADMIN-003');
        $supplier = Supplier::query()->create(['name' => 'Main Vendor']);

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.store'), [
                'reference' => 'PO-ADMIN-300',
                'supplier_id' => $supplier->id,
                'lines' => [
                    ['sku' => $variant->sku, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'reference' => 'PO-ADMIN-300',
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Main Vendor',
        ]);
    }

    public function test_user_with_manage_permission_can_cancel_purchase_order_line(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ADMIN-004');
        $order = app(PurchaseOrderService::class)->create('PO-ADMIN-400', [
            ['sku' => $variant->sku, 'quantity' => 5],
        ]);
        $line = $order->lines()->first();

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.lines.cancel', [$order, $line]))
            ->assertRedirect(route('admin.inventory.purchase-orders.show', $order));

        $line->refresh();

        $this->assertSame(0, $line->quantity_received);
        $this->assertSame(0, $line->quantity_ordered);
        $this->assertSame(0, $line->incomingQuantity());
    }

    public function test_purchase_order_index_can_filter_by_supplier(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-FILTER-001');
        $supplier = Supplier::query()->create(['name' => 'Filter Vendor']);

        app(PurchaseOrderService::class)->create(
            reference: 'PO-FILTER-A',
            lines: [['sku' => $variant->sku, 'quantity' => 2]],
            supplierId: $supplier->id,
        );

        app(PurchaseOrderService::class)->create(
            reference: 'PO-FILTER-B',
            lines: [['sku' => $variant->sku, 'quantity' => 1]],
            supplierName: 'Other Vendor',
        );

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.index', ['supplier_id' => $supplier->id]))
            ->assertOk()
            ->assertSee('PO-FILTER-A')
            ->assertDontSee('PO-FILTER-B');
    }

    public function test_purchase_order_export_returns_csv(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-EXPORT-001');

        app(PurchaseOrderService::class)->create('PO-EXPORT-A', [
            ['sku' => $variant->sku, 'quantity' => 2],
        ]);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('PO-EXPORT-A', $response->streamedContent());
    }

    public function test_purchase_order_print_page_renders(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-PRINT-001');
        $order = app(PurchaseOrderService::class)->create('PO-PRINT-A', [
            ['sku' => $variant->sku, 'quantity' => 2],
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.print', $order))
            ->assertOk()
            ->assertSee('PO-PRINT-A')
            ->assertSee('Download PDF');
    }

    public function test_user_can_download_purchase_order_pdf(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-PDF-001');
        $order = app(PurchaseOrderService::class)->create('PO-PDF-A', [
            ['sku' => $variant->sku, 'quantity' => 2],
        ]);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.pdf', $order));

        $response->assertOk();
        $this->assertStringStartsWith('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_user_can_view_purchase_order_analytics(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ANALYTICS-001');
        $supplier = Supplier::query()->create(['name' => 'Analytics Vendor']);

        app(PurchaseOrderService::class)->create(
            reference: 'PO-ANALYTICS-A',
            lines: [['sku' => $variant->sku, 'quantity' => 3]],
            supplierId: $supplier->id,
        );

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.analytics'))
            ->assertOk()
            ->assertSee('Purchase order analytics')
            ->assertSee('Analytics Vendor')
            ->assertSee('Total POs');
    }

    public function test_purchase_order_analytics_export_returns_csv(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-ANALYTICS-EXPORT-001');

        app(PurchaseOrderService::class)->create('PO-ANALYTICS-EXPORT-A', [
            ['sku' => $variant->sku, 'quantity' => 2],
        ]);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.analytics.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('PO-ANALYTICS-EXPORT-A', $response->streamedContent());
    }

    public function test_purchase_order_line_stores_unit_cost(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-COST-001');
        $variant->update(['cost' => 12.50]);

        $order = app(PurchaseOrderService::class)->create('PO-COST-A', [
            ['sku' => $variant->sku, 'quantity' => 3, 'unit_cost' => 15.00],
        ]);

        $line = $order->lines()->first();

        $this->assertSame('15.00', (string) $line->unit_cost);
        $this->assertSame(45.0, $line->orderedValue());
    }

    public function test_user_can_email_purchase_order_pdf_to_supplier(): void
    {
        Mail::fake();

        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-EMAIL-001');
        $supplier = Supplier::query()->create([
            'name' => 'Email Vendor',
            'email' => 'vendor@example.com',
        ]);

        $order = app(PurchaseOrderService::class)->create(
            reference: 'PO-EMAIL-A',
            lines: [['sku' => $variant->sku, 'quantity' => 2]],
            supplierId: $supplier->id,
        );

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.email', $order))
            ->assertRedirect(route('admin.inventory.purchase-orders.show', $order));

        Mail::assertQueued(PurchaseOrderPdfMail::class);
    }

    public function test_purchase_order_email_can_send_synchronously_when_queue_disabled(): void
    {
        Mail::fake();
        config(['inventory.purchase_order.queue_emails' => false]);

        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-EMAIL-SYNC-001');
        $supplier = Supplier::query()->create([
            'name' => 'Sync Email Vendor',
            'email' => 'sync-vendor@example.com',
        ]);

        $order = app(PurchaseOrderService::class)->create(
            reference: 'PO-EMAIL-SYNC-A',
            lines: [['sku' => $variant->sku, 'quantity' => 2]],
            supplierId: $supplier->id,
        );

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.email', $order))
            ->assertRedirect();

        Mail::assertSent(PurchaseOrderPdfMail::class);
        Mail::assertNotQueued(PurchaseOrderPdfMail::class);
    }

    public function test_create_purchase_order_can_auto_email_supplier(): void
    {
        Mail::fake();

        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-AUTO-EMAIL-001');
        $supplier = Supplier::query()->create([
            'name' => 'Auto Email Vendor',
            'email' => 'auto-vendor@example.com',
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.store'), [
                'reference' => 'PO-AUTO-EMAIL-A',
                'supplier_id' => $supplier->id,
                'send_email' => '1',
                'lines' => [
                    ['sku' => $variant->sku, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        Mail::assertQueued(PurchaseOrderPdfMail::class);
    }

    public function test_purchase_order_stores_currency(): void
    {
        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-CURRENCY-001');

        $order = app(PurchaseOrderService::class)->create(
            reference: 'PO-CURRENCY-A',
            lines: [['sku' => $variant->sku, 'quantity' => 2]],
            currency: 'EUR',
        );

        $this->assertSame('EUR', $order->currency);
    }

    public function test_purchase_order_mail_uses_notification_template_subject(): void
    {
        $order = new PurchaseOrder([
            'reference' => 'PO-TEMPLATE-001',
            'supplier_name' => 'Acme Supplies',
        ]);

        $resolved = app(PurchaseOrderMailTemplate::class)->resolve($order);

        $this->assertSame('Purchase Order PO-TEMPLATE-001', $resolved['subject']);
        $this->assertSame('inventory::mail.purchase-order', $resolved['view']);
    }
}
