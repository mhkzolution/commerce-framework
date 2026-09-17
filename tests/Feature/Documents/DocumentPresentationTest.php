<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Documents\Services\DocumentQueryService;
use Commerce\Documents\Services\TaxInvoiceIssueService;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Product\Models\ProductVariant;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class DocumentPresentationTest extends TestCase
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

    public function test_document_view_renders_frozen_payload_and_is_ok(): void
    {
        $document = $this->issuePaidInvoice();
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee($document->number, false)
            ->assertSee('Mina Shore', false);

        $this->actingAs($admin)
            ->get(route('admin.documents.show', $document))
            ->assertOk()
            ->assertSee('data-document-view', false)
            ->assertSee('ใบกำกับภาษี', false)
            ->assertSee('Tax Invoice', false)
            ->assertSee('ผู้ขาย / Seller', false)
            ->assertSee('ผู้ซื้อ / Buyer', false)
            ->assertSee('Acme Co', false)
            ->assertSee('Mina Shore', false)
            ->assertSee($document->number, false)
            ->assertSee('ยอดรวม / Grand total', false)
            ->assertDontSee('Regenerate', false);

        $this->assertNull($document->fresh()?->pdf_path);
        $this->assertFalse(
            DocumentEvent::query()
                ->where('document_id', $document->id)
                ->where('event', DocumentEvent::PRINTED)
                ->exists(),
        );
    }

    public function test_print_uses_a4_portrait_html_and_records_printed(): void
    {
        $document = $this->issuePaidInvoice();

        $this->actingAs(User::query()->first())
            ->get(route('admin.documents.print', $document))
            ->assertOk()
            ->assertSee('size: A4 portrait', false)
            ->assertSee('window.print()', false)
            ->assertSee($document->number, false);

        $this->assertTrue(
            DocumentEvent::query()
                ->where('document_id', $document->id)
                ->where('event', DocumentEvent::PRINTED)
                ->exists(),
        );
        $this->assertNull($document->fresh()?->pdf_path);
    }

    public function test_download_generates_pdf_once_and_reuses_the_cached_path(): void
    {
        $document = $this->issuePaidInvoice();
        $payload = $document->payload;
        $admin = User::query()->first();

        $first = $this->actingAs($admin)
            ->get(route('admin.documents.download', $document))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $first->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $first->getContent());

        $document->refresh();
        $this->assertNotNull($document->pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($document->pdf_path));
        $this->assertSame($payload, $document->payload);
        $this->assertSame(1, DocumentEvent::query()->where('document_id', $document->id)->where('event', DocumentEvent::PDF_GENERATED)->count());
        $this->assertSame(1, DocumentEvent::query()->where('document_id', $document->id)->where('event', DocumentEvent::DOWNLOADED)->count());

        $cachedPath = $document->pdf_path;
        $generatedAt = DocumentEvent::query()
            ->where('document_id', $document->id)
            ->where('event', DocumentEvent::PDF_GENERATED)
            ->value('created_at');

        $second = $this->actingAs($admin)
            ->get(route('admin.documents.download', $document))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', (string) $second->headers->get('content-type'));
        $this->assertSame($cachedPath, $document->fresh()?->pdf_path);
        $this->assertSame($payload, $document->fresh()?->payload);
        $this->assertSame(1, DocumentEvent::query()->where('document_id', $document->id)->where('event', DocumentEvent::PDF_GENERATED)->count());
        $this->assertSame(2, DocumentEvent::query()->where('document_id', $document->id)->where('event', DocumentEvent::DOWNLOADED)->count());
        $this->assertEquals($generatedAt, DocumentEvent::query()
            ->where('document_id', $document->id)
            ->where('event', DocumentEvent::PDF_GENERATED)
            ->value('created_at'));
    }

    public function test_download_does_not_mutate_payload_after_company_settings_change(): void
    {
        $document = $this->issuePaidInvoice();

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.company.update'), [
                'name' => 'Changed Co',
                'tax_id' => '0105555555551',
                'branch_no' => '00000',
            ])
            ->assertRedirect(route('admin.settings.company.show'));

        $this->actingAs(User::query()->first())
            ->get(route('admin.documents.download', $document))
            ->assertOk();

        $this->assertSame('Acme Co', $document->fresh()?->payload['seller']['name'] ?? null);

        $this->actingAs(User::query()->first())
            ->get(route('admin.documents.show', $document))
            ->assertOk()
            ->assertSee('Acme Co', false)
            ->assertDontSee('Changed Co', false);
    }

    public function test_staff_with_document_view_permission_can_open_documents(): void
    {
        $document = $this->issuePaidInvoice();
        $viewer = $this->userWithPermissions(['documents.document.view']);

        $this->actingAs($viewer)
            ->get(route('admin.documents.index'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('admin.documents.show', $document))
            ->assertOk();
    }

    public function test_staff_without_document_view_permission_is_forbidden(): void
    {
        $document = $this->issuePaidInvoice();
        $viewer = $this->userWithPermissions(['orders.order.view']);

        $this->actingAs($viewer)
            ->get(route('admin.documents.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.documents.show', $document))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.documents.print', $document))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.documents.download', $document))
            ->assertForbidden();
    }

    public function test_index_filters_by_type_and_search(): void
    {
        $document = $this->issuePaidInvoice();
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.documents.index', ['type' => 'tax_invoice']))
            ->assertOk()
            ->assertSee($document->number, false);

        $this->actingAs($admin)
            ->get(route('admin.documents.index', ['type' => 'receipt']))
            ->assertOk()
            ->assertDontSee($document->number, false);

        $this->actingAs($admin)
            ->get(route('admin.documents.index', ['search' => $document->number]))
            ->assertOk()
            ->assertSee($document->number, false);

        $this->actingAs($admin)
            ->get(route('admin.documents.index', ['search' => 'NO-SUCH-DOCUMENT']))
            ->assertOk()
            ->assertDontSee($document->number, false);
    }

    public function test_index_paginates_documents(): void
    {
        $first = $this->issuePaidInvoice();
        $second = $this->issuePaidInvoice();

        $page = app(DocumentQueryService::class)->paginate(perPage: 1);

        $this->assertSame(2, $page->total());
        $this->assertTrue($page->hasPages());
        $this->assertCount(1, $page->items());
        $this->assertTrue(
            collect([$first->number, $second->number])->contains($page->items()[0]->number),
        );
    }

    public function test_order_detail_links_to_the_issued_document(): void
    {
        $document = $this->issuePaidInvoice();
        $order = Order::query()->findOrFail($document->source_id);

        $this->actingAs(User::query()->first())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(route('admin.documents.show', $document), false)
            ->assertSee($document->number, false);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->create([
            'code' => 'document-viewer-'.Str::random(6),
            'name' => 'Limited',
            'is_system' => false,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id'),
        );

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: 'Limited Staff',
            email: 'docs-limited-'.Str::random(6).'@example.test',
            password: 'password',
            roleCodes: [$role->code],
        ));
        app(AuthorizationService::class)->clearCacheForUser($user->id);

        return $user;
    }

    private function issuePaidInvoice(): Document
    {
        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.company.update'), [
                'name' => 'Acme Co',
                'tax_id' => '0105555555551',
                'branch_no' => '00000',
                'address' => '88 Silom Rd',
            ])
            ->assertRedirect();

        $order = $this->createAdminOrder();
        $payment = app(PaymentServiceInterface::class)->createForOrder(
            $order->uuid,
            $order->grand_total,
            $order->currency,
        );
        app(PaymentServiceInterface::class)->markPaid($payment->uuid, 'INV-PAY-'.Str::random(8));

        return app(TaxInvoiceIssueService::class)->issue(
            $order->fresh(['lineItems']),
            BuyerTaxData::fromArray([
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
            User::query()->first()?->id,
        );
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
