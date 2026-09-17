<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Models\DocumentSequence;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Enums\PaperWidth;
use Commerce\Pos\Enums\PrintJobType;
use Commerce\Pos\Enums\PrintRenderer;
use Commerce\Pos\Models\PosPrintJob;
use Commerce\Pos\Models\PosSlipSequence;
use Commerce\Pos\Models\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class PosThermalSlipFrameworkTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Carbon::setTestNow('2026-09-17 14:30:00');
    }

    public function test_checkout_allocates_a_pos_slip_number_without_documents(): void
    {
        $checkout = $this->checkoutSku('POS-SLIP-001', 1500);

        $checkout->assertOk()
            ->assertJsonPath('receipt.slip_number', 'POS-20260917-000001')
            ->assertJsonPath('receipt.print_urls.58mm', route('pos.receipt.show', [
                'orderUuid' => $checkout->json('receipt.order_uuid'),
                'paper_width' => '58mm',
            ]))
            ->assertJsonPath('receipt.print_urls.80mm', route('pos.receipt.show', [
                'orderUuid' => $checkout->json('receipt.order_uuid'),
                'paper_width' => '80mm',
            ]));

        $order = Order::query()->where('uuid', $checkout->json('receipt.order_uuid'))->first();
        $this->assertNotNull($order);
        $this->assertSame('pos', $order->channel);
        $this->assertSame('POS-20260917-000001', $order->pos_slip_number);
        $this->assertMatchesRegularExpression('/^POS-\d{8}-\d{6}$/', (string) $order->pos_slip_number);

        $this->assertSame(0, PosPrintJob::query()->count());
        $this->assertNoAccountingDocuments();
    }

    public function test_print_opens_browser_print_jobs_for_58mm_and_80mm(): void
    {
        $admin = User::query()->first();
        $checkout = $this->checkoutSku('POS-SLIP-PRINT-001', 2000);
        $orderUuid = $checkout->json('receipt.order_uuid');
        $slipNumber = $checkout->json('receipt.slip_number');

        $eighty = $this->actingAs($admin)
            ->get(route('pos.receipt.show', ['orderUuid' => $orderUuid, 'paper_width' => '80mm']))
            ->assertOk();

        $eighty->assertSee('SLIP', false)
            ->assertSee($slipNumber, false)
            ->assertSee('POS-SLIP-PRINT-001', false)
            ->assertSee('data-paper-width="80mm"', false)
            ->assertSee('data-print-renderer="browser-print"', false)
            ->assertSee('data-print-type="slip"', false)
            ->assertSee('onload="window.print()"', false)
            ->assertDontSee('RECEIPT', false);

        $fiftyEight = $this->actingAs($admin)
            ->get(route('pos.receipt.show', ['orderUuid' => $orderUuid, 'paper_width' => '58mm']))
            ->assertOk();

        $fiftyEight->assertSee('data-paper-width="58mm"', false)
            ->assertSee($slipNumber, false)
            ->assertSee('SLIP', false);

        $jobs = PosPrintJob::query()->orderBy('id')->get();
        $this->assertCount(2, $jobs);
        $this->assertTrue($jobs->every(fn (PosPrintJob $job): bool => $job->type === PrintJobType::Slip));
        $this->assertTrue($jobs->every(fn (PosPrintJob $job): bool => $job->renderer === PrintRenderer::BrowserPrint));
        $this->assertTrue($jobs->every(fn (PosPrintJob $job): bool => $job->template === 'slip'));
        $this->assertTrue($jobs->every(fn (PosPrintJob $job): bool => $job->slip_number === $slipNumber));
        $this->assertSame(PaperWidth::Eighty, $jobs[0]->paper_width);
        $this->assertSame(PaperWidth::FiftyEight, $jobs[1]->paper_width);
        $this->assertNotNull($jobs[0]->printed_at);
        $this->assertSame('printed', $jobs[0]->status);
        $this->assertSame($slipNumber, $jobs[0]->payload['slip_number'] ?? null);
        $this->assertNotEmpty($jobs[0]->payload['lines'] ?? []);

        $this->assertNoAccountingDocuments();
    }

    public function test_reprint_keeps_the_original_slip_number(): void
    {
        $admin = User::query()->first();
        $checkout = $this->checkoutSku('POS-SLIP-REPRINT-001', 1800);
        $orderUuid = $checkout->json('receipt.order_uuid');
        $slipNumber = $checkout->json('receipt.slip_number');

        $this->actingAs($admin)
            ->get(route('pos.receipt.show', ['orderUuid' => $orderUuid, 'paper_width' => '80mm']))
            ->assertOk()
            ->assertSee($slipNumber, false);

        $this->actingAs($admin)
            ->get(route('pos.receipt.show', ['orderUuid' => $orderUuid, 'paper_width' => '80mm']))
            ->assertOk()
            ->assertSee($slipNumber, false);

        $order = Order::query()->where('uuid', $orderUuid)->first();
        $this->assertSame($slipNumber, $order?->pos_slip_number);
        $this->assertSame(1, (int) PosSlipSequence::query()->value('last_value'));
        $this->assertCount(2, PosPrintJob::query()->get());
        $this->assertTrue(PosPrintJob::query()->get()->every(
            fn (PosPrintJob $job): bool => $job->slip_number === $slipNumber,
        ));
        $this->assertNoAccountingDocuments();
    }

    public function test_orders_index_finds_a_sale_by_pos_slip_number(): void
    {
        $admin = User::query()->first();
        $checkout = $this->checkoutSku('POS-SLIP-SEARCH-001', 1200);
        $slipNumber = $checkout->json('receipt.slip_number');

        $this->actingAs($admin)
            ->get(route('pos.orders.index', ['search' => $slipNumber]))
            ->assertOk()
            ->assertSee($slipNumber, false)
            ->assertSee($checkout->json('receipt.order_number'), false);
    }

    public function test_slip_wraps_a_long_product_name(): void
    {
        $admin = User::query()->first();
        $longName = 'ข้าวหอมมะลิแท้ 100% คัดพิเศษส่งออก 5 กิโลกรัม';
        Register::query()->create([
            'name' => 'Long Name Counter',
            'code' => 'POS-LONG',
            'is_active' => true,
        ]);
        $variant = $this->createPurchasableProduct(price: 2500, stock: 5, sku: 'POS-LONG-001');
        $variant->update(['name' => $longName]);
        $variant->product->update(['name' => $longName]);

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => 'POS-LONG-001'])->assertOk();
        $checkout = $this->actingAs($admin)->postJson(route('pos.api.checkout'), [
            'payment_method' => 'cash',
            'payments' => [['method' => 'cash', 'amount_minor' => 2500]],
        ])->assertOk();

        $this->actingAs($admin)
            ->get(route('pos.receipt.show', [
                'orderUuid' => $checkout->json('receipt.order_uuid'),
                'paper_width' => '58mm',
            ]))
            ->assertOk()
            ->assertSee($longName, false)
            ->assertSee('overflow-wrap: anywhere', false)
            ->assertSee('table-layout: fixed', false);
    }

    public function test_storefront_orders_do_not_receive_pos_slip_numbers(): void
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-WEB-000001',
            'status' => 'pending',
            'currency' => 'THB',
            'channel' => 'web',
            'subtotal' => 1000,
            'grand_total' => 1000,
        ]);

        $this->assertNull($order->fresh()->pos_slip_number);
        $this->assertDatabaseCount('pos_slip_sequences', 0);
    }

    private function checkoutSku(string $sku, int $price): TestResponse
    {
        $admin = User::query()->first();
        Register::query()->create([
            'name' => 'Slip Counter',
            'code' => 'POS-SLIP-'.substr($sku, -6),
            'is_active' => true,
        ]);
        $this->createPurchasableProduct(price: $price, stock: 5, sku: $sku);

        $this->actingAs($admin)->post(route('pos.session.open'));
        $this->actingAs($admin)->postJson(route('pos.api.cart.items.store'), ['sku' => $sku])->assertOk();

        return $this->actingAs($admin)->postJson(route('pos.api.checkout'), [
            'payment_method' => 'cash',
            'payments' => [['method' => 'cash', 'amount_minor' => $price]],
        ]);
    }

    private function assertNoAccountingDocuments(): void
    {
        if (Schema::hasTable('documents')) {
            $this->assertSame(0, Document::query()->count());
        }

        if (Schema::hasTable('document_sequences')) {
            $this->assertSame(0, DocumentSequence::query()->count());
        }

        if (Schema::hasTable('document_events')) {
            $this->assertSame(0, DocumentEvent::query()->count());
        }
    }
}
