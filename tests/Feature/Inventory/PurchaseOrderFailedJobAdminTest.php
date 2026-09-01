<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Inventory\Mail\PurchaseOrderPdfMail;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PurchaseOrderFailedJobAdminTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_user_can_view_failed_purchase_order_email_jobs(): void
    {
        $this->seedFailedPurchaseOrderEmailJob();

        $this->actingAs(User::query()->first())
            ->get(route('admin.inventory.purchase-orders.failed-jobs.index'))
            ->assertOk()
            ->assertSee('Failed purchase order emails')
            ->assertSee('SMTP connection failed');
    }

    public function test_user_can_retry_failed_purchase_order_email_job(): void
    {
        $uuid = $this->seedFailedPurchaseOrderEmailJob();

        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:retry', ['id' => [$uuid]])
            ->andReturn(0);

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.failed-jobs.retry', $uuid))
            ->assertRedirect(route('admin.inventory.purchase-orders.failed-jobs.index'));
    }

    public function test_user_can_dismiss_failed_purchase_order_email_job(): void
    {
        $uuid = $this->seedFailedPurchaseOrderEmailJob();

        $this->actingAs(User::query()->first())
            ->delete(route('admin.inventory.purchase-orders.failed-jobs.destroy', $uuid))
            ->assertRedirect(route('admin.inventory.purchase-orders.failed-jobs.index'));

        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $uuid]);
    }

    public function test_purchase_order_email_is_queued_on_notifications_queue(): void
    {
        Mail::fake();

        $variant = $this->createPurchasableProduct(price: 50, stock: 1, sku: 'PO-QUEUE-NAME-001');
        $supplier = Supplier::query()->create([
            'name' => 'Queue Vendor',
            'email' => 'queue-vendor@example.com',
        ]);

        $order = app(PurchaseOrderService::class)->create(
            reference: 'PO-QUEUE-NAME-A',
            lines: [['sku' => $variant->sku, 'quantity' => 1]],
            supplierId: $supplier->id,
        );

        $this->actingAs(User::query()->first())
            ->post(route('admin.inventory.purchase-orders.email', $order));

        Mail::assertQueued(
            PurchaseOrderPdfMail::class,
            fn (PurchaseOrderPdfMail $mail): bool => $mail->queue === 'notifications',
        );
    }

    private function seedFailedPurchaseOrderEmailJob(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode([
                'displayName' => PurchaseOrderPdfMail::class,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => [
                    'commandName' => 'Illuminate\\Mail\\SendQueuedMailable',
                    'command' => 'O:47:"Illuminate\\Mail\\SendQueuedMailable":1:{s:5:"order";O:45:"Commerce\\Inventory\\Models\\PurchaseOrder":1:{s:9:"reference";s:17:"PO-FAILED-EMAIL-A";}}',
                ],
            ]),
            'exception' => "RuntimeException: SMTP connection failed\n#0 /tmp/example.php(1): mail()",
            'failed_at' => now(),
        ]);

        return $uuid;
    }
}
