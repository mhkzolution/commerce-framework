<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Contracts\DocumentTypeHandler;
use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\DTO\CompanyProfileData;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Handlers\TaxInvoiceHandler;
use Commerce\Documents\Registry\DocumentTypeRegistry;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderLineItem;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentTypeHandlerContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_handler_must_return_type(): void
    {
        foreach ($this->registeredHandlers() as $handler) {
            $this->assertInstanceOf(DocumentType::class, $handler->type());
            $this->assertSame($handler, app(DocumentTypeRegistry::class)->handlerFor($handler->type()));
        }

        $this->assertSame(DocumentType::TaxInvoice, app(TaxInvoiceHandler::class)->type());
    }

    public function test_handler_must_return_prefix(): void
    {
        foreach ($this->registeredHandlers() as $handler) {
            $this->assertSame($handler->type()->prefix(), $handler->prefix());
            $this->assertGreaterThan(0, $handler->numberWidth());
        }

        $this->assertSame('INV', app(TaxInvoiceHandler::class)->prefix());
    }

    public function test_handler_must_build_payload(): void
    {
        $handler = app(TaxInvoiceHandler::class);
        $order = $this->orderWithLine();
        $payload = $handler->buildPayload($this->taxInvoiceContext($order, 'INV-202609-000001'));

        $this->assertSame(TaxInvoiceHandler::SCHEMA_VERSION, $payload['schema_version']);
        $this->assertSame(DocumentType::TaxInvoice->value, $payload['document']['type']);
        $this->assertSame('INV-202609-000001', $payload['document']['number']);
        $this->assertNotSame('', $payload['document']['issued_at']);
        $this->assertSame(Order::REFERENCE_TYPE, $payload['source']['type']);
        $this->assertSame((int) $order->id, $payload['source']['id']);
        $this->assertNotSame('', $payload['source']['number']);
        $this->assertCount(1, $payload['lines']);
        $this->assertSame('Widget', $payload['lines'][0]['name']);
        $this->assertSame(10700, $payload['totals']['grand_total']);
        $this->assertSame('THB', $payload['totals']['currency']);
        $this->assertSame('Acme Co', $payload['seller']['name']);
        $this->assertSame('Mina Shore', $payload['buyer']['company_name']);
    }

    public function test_handler_build_payload_rejects_incomplete_context(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Tax invoice payload requires an order source.');

        app(TaxInvoiceHandler::class)->buildPayload([]);
    }

    public function test_registry_rejects_handler_whose_prefix_does_not_match_type(): void
    {
        $registry = new DocumentTypeRegistry;
        $handler = new class implements DocumentTypeHandler
        {
            public function type(): DocumentType
            {
                return DocumentType::Receipt;
            }

            public function prefix(): string
            {
                return 'WRONG';
            }

            public function numberWidth(): int
            {
                return 6;
            }

            public function buildPayload(array $context): array
            {
                return [];
            }
        };

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Document handler prefix must match DocumentType::prefix().');

        $registry->register($handler);
    }

    /**
     * @return list<DocumentTypeHandler>
     */
    private function registeredHandlers(): array
    {
        $handlers = app(DocumentTypeRegistry::class)->all();

        $this->assertNotEmpty($handlers);

        return $handlers;
    }

    /**
     * @return array<string, mixed>
     */
    private function taxInvoiceContext(Order $order, string $number): array
    {
        return [
            'source' => $order,
            'seller' => new CompanyProfileData(
                name: 'Acme Co',
                taxId: '0105555555551',
                branchNo: '00000',
                address: '88 Silom Rd',
                phone: '021234567',
                email: 'tax@acme.test',
                logo: '',
            ),
            'buyer' => BuyerTaxData::fromArray([
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
            ]),
            'number' => $number,
            'issued_at' => new DateTimeImmutable('2026-09-17 10:00:00'),
        ];
    }

    private function orderWithLine(): Order
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-202609-000001',
            'status' => 'completed',
            'currency' => 'THB',
            'subtotal' => 10000,
            'discount_total' => 0,
            'tax_total' => 700,
            'shipping_total' => 0,
            'grand_total' => 10700,
        ]);

        OrderLineItem::query()->create([
            'order_id' => $order->id,
            'purchasable_uuid' => '00000000-0000-0000-0000-000000000001',
            'sku' => 'WDG-1',
            'name' => 'Widget',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        return $order->load('lineItems');
    }
}
