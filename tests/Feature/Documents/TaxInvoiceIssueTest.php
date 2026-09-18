<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Services\CompanyProfileService;
use Commerce\Documents\Services\TaxInvoiceIssueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Product\Models\ProductVariant;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class TaxInvoiceIssueTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-09-17 10:00:00');
    }

    public function test_paid_order_issues_inv_number_and_frozen_payload(): void
    {
        $this->saveCompany(['name' => 'Acme Co', 'tax_id' => '0105555555551']);
        $order = $this->paidOrder();
        $document = $this->issue($order);

        $this->assertSame('INV-202609-000001', $document->number);
        $this->assertSame(DocumentType::TaxInvoice, $document->type);
        $this->assertSame(DocumentStatus::Issued, $document->status);
        $this->assertSame(Order::REFERENCE_TYPE, $document->source_type);
        $this->assertSame((int) $order->id, $document->source_id);
        $this->assertNull($document->pdf_path);
        $this->assertSame((int) $order->grand_total, $document->grand_total);
        $this->assertSame('THB', $document->currency);
        $this->assertSame(1, $document->payload['schema_version']);
        $this->assertSame('Acme Co', $document->payload['seller']['name']);
        $this->assertSame('0105555555551', $document->payload['seller']['tax_id']);
        $this->assertSame('Mina Shore', $document->payload['buyer']['company_name']);
        $this->assertSame($order->order_number, $document->payload['source']['number']);
        $this->assertNotEmpty($document->payload['lines']);
        $this->assertTrue(
            DocumentEvent::query()
                ->where('document_id', $document->id)
                ->where('event', DocumentEvent::ISSUED)
                ->exists(),
        );

        $this->saveCompany(['name' => 'Changed Co', 'tax_id' => '0105555555551']);

        $this->assertSame('Acme Co', $document->fresh()->payload['seller']['name']);
    }

    public function test_completed_unpaid_order_can_be_invoiced(): void
    {
        $this->saveCompany();
        $admin = User::query()->first();
        $order = $this->createAdminOrder();

        $this->actingAs($admin)->post(route('admin.orders.confirm', $order));
        $this->actingAs($admin)->post(route('admin.orders.complete', $order));

        $document = $this->issue($order->fresh());

        $this->assertSame('INV-202609-000001', $document->number);
    }

    public function test_unpaid_pending_order_cannot_be_invoiced(): void
    {
        $this->saveCompany();
        $order = $this->createAdminOrder();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A tax invoice can only be issued for a paid or completed order.');

        $this->issue($order);
    }

    public function test_cancelled_order_cannot_be_invoiced(): void
    {
        $this->saveCompany();
        $admin = User::query()->first();
        $order = $this->paidOrder();

        $this->actingAs($admin)->post(route('admin.orders.cancel', $order));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A tax invoice cannot be issued for a cancelled order.');

        $this->issue($order->fresh());
    }

    public function test_duplicate_tax_invoice_for_the_same_order_is_blocked(): void
    {
        $this->saveCompany();
        $order = $this->paidOrder();
        $this->issue($order);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A tax invoice already exists for this order.');

        $this->issue($order);
    }

    public function test_missing_company_tax_id_is_rejected(): void
    {
        app(CompanyProfileService::class)->ensureRegistered();
        $order = $this->paidOrder();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Company tax information is incomplete');

        $this->issue($order);
    }

    public function test_admin_can_issue_a_tax_invoice_from_the_order_detail_modal(): void
    {
        $this->saveCompany();
        $admin = User::query()->first();
        $order = $this->paidOrder();

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(__('documents::admin.generate_tax_invoice'), false)
            ->assertSee('id="tax-invoice-dialog"', false)
            ->assertSee('data-thailand-address', false)
            ->assertSee('name="billing_address[province]"', false);

        $this->actingAs($admin)
            ->post(route('admin.orders.tax-invoice.store', $order), $this->buyerPayload())
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $this->assertSame('INV-202609-000001', Document::query()->value('number'));

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.show', $order->fresh()))
            ->assertOk()
            ->assertDontSee(__('documents::admin.generate_tax_invoice'), false)
            ->getContent();

        $this->assertStringContainsString('data-tax-invoice-number="INV-202609-000001"', $html);
    }

    public function test_customer_tax_profile_prefills_the_issue_modal(): void
    {
        $this->saveCompany();
        $admin = User::query()->first();
        $customer = app(CustomerServiceInterface::class)->create(new CreateCustomerData(
            email: 'buyer-tax@example.com',
            name: 'Buyer Ltd',
            phone: '0812345678',
        ));

        $this->actingAs($admin)
            ->put(route('admin.customers.tax-profile.update', $customer), $this->buyerPayload([
                'company_name' => 'Buyer Ltd',
                'tax_id' => '0105544444444',
            ]))
            ->assertRedirect(route('admin.customers.edit', $customer));

        $order = $this->paidOrder(overrides: [
            'customer_uuid' => $customer->uuid,
            'customer_name' => 'Buyer Ltd',
            'customer_email' => $customer->email,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Buyer Ltd', false)
            ->assertSee('0105544444444', false);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveCompany(array $values = []): void
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.company.update'), array_merge([
                'name' => 'Acme Co',
                'tax_id' => '0105555555551',
                'branch_no' => '00000',
                'address' => '88 Silom Rd',
                'phone' => '021234567',
                'email' => 'tax@acme.test',
            ], $values))
            ->assertRedirect(route('admin.settings.company.show'));
    }

    private function paidOrder(?ProductVariant $variant = null, array $overrides = []): Order
    {
        $order = $this->createAdminOrder($variant, $overrides);
        $payment = app(PaymentServiceInterface::class)->createForOrder(
            $order->uuid,
            $order->grand_total,
            $order->currency,
        );
        app(PaymentServiceInterface::class)->markPaid($payment->uuid, 'INV-PAY');

        return $order->fresh(['lineItems']);
    }

    private function issue(Order $order, array $buyer = []): Document
    {
        return app(TaxInvoiceIssueService::class)->issue(
            $order,
            BuyerTaxData::fromArray($this->buyerPayload($buyer)),
            User::query()->first()?->id,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function buyerPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'company_name' => 'Mina Shore',
            'tax_id' => '1234567890123',
            'branch_no' => '00000',
            'billing_address' => [
                'line1' => '12 Rama IV',
                'district' => 'Bang Rak',
                'province' => 'Bangkok',
                'postal_code' => '10500',
                'country_code' => 'TH',
            ],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAdminOrder(?ProductVariant $variant = null, array $overrides = []): Order
    {
        $variant ??= $this->createPurchasableProduct(stock: 8);

        $this->actingAs(User::query()->first())
            ->post(route('admin.orders.store'), array_replace_recursive([
                'intent' => 'create',
                'idempotency_key' => (string) Str::uuid(),
                'customer_name' => 'Mina Shore',
                'customer_email' => 'mina@example.com',
                'customer_phone' => '0890001111',
                'billing_same_as_shipping' => '1',
                'shipping_address' => [
                    'recipient_name' => 'Mina Shore',
                    'phone' => '0890001111',
                    'line1' => '12 Rama IV',
                    'district' => 'Bang Rak',
                    'province' => 'Bangkok',
                    'postal_code' => '10500',
                ],
                'lines' => [
                    ['purchasable_uuid' => $variant->uuid, 'quantity' => 1],
                ],
            ], $overrides))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);

        return $order->load('lineItems');
    }
}
