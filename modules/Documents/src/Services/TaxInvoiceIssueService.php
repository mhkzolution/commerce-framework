<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\DTO\BuyerTaxData;
use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Handlers\TaxInvoiceHandler;
use Commerce\Documents\Models\Document;
use Commerce\Documents\Models\DocumentEvent;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Support\OrderFinancialStatus;
use Commerce\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

final class TaxInvoiceIssueService extends BaseService
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly TaxInvoiceHandler $handler,
        private readonly CompanyProfileService $company,
        private readonly CustomerTaxProfileService $taxProfiles,
    ) {}

    public function issue(Order $order, BuyerTaxData $buyer, ?int $createdBy = null): Document
    {
        $buyer->assertComplete();
        $seller = $this->company->requireForIssue();

        return DB::transaction(function () use ($order, $buyer, $seller, $createdBy): Document {
            /** @var Order $locked */
            $locked = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->handler->assertCanIssue($locked, $this->findIssued($locked));

            if (! $this->isEligible($locked)) {
                throw new DomainException('A tax invoice can only be issued for a paid or completed order.');
            }

            $issuedAt = now();
            $number = $this->documents->allocateNumber(
                DocumentType::TaxInvoice,
                $issuedAt,
                $locked->tenant_id !== null ? (int) $locked->tenant_id : null,
            );
            $payload = $this->handler->buildPayload([
                'source' => $locked,
                'seller' => $seller,
                'buyer' => $buyer,
                'number' => $number,
                'issued_at' => $issuedAt,
            ]);
            $customer = $this->taxProfiles->customerForOrder($locked);

            $document = Document::query()->create([
                'tenant_id' => $locked->tenant_id,
                'type' => DocumentType::TaxInvoice,
                'number' => $number,
                'source_type' => $this->handler->sourceType(),
                'source_id' => $locked->getKey(),
                'customer_id' => $customer?->getKey(),
                'status' => DocumentStatus::Issued,
                'issued_at' => $issuedAt,
                'pdf_path' => null,
                'payload' => $payload,
                'grand_total' => (int) $locked->grand_total,
                'currency' => (string) $locked->currency,
                'created_by' => $createdBy,
            ]);

            $this->documents->recordEvent($document, DocumentEvent::ISSUED, $createdBy, [
                'number' => $number,
                'source_type' => $document->source_type,
                'source_id' => $document->source_id,
            ]);

            return $document;
        });
    }

    public function findIssued(Order $order): ?Document
    {
        return Document::query()
            ->where('type', DocumentType::TaxInvoice)
            ->where('source_type', $this->handler->sourceType())
            ->where('source_id', $order->getKey())
            ->where('status', DocumentStatus::Issued)
            ->first();
    }

    public function isEligible(Order $order): bool
    {
        if ($order->isCancelled()) {
            return false;
        }

        if ($this->findIssued($order) instanceof Document) {
            return false;
        }

        return $order->isCompleted() || $this->isPaid($order);
    }

    public function prefill(Order $order): BuyerTaxData
    {
        return $this->taxProfiles->prefill($order);
    }

    private function isPaid(Order $order): bool
    {
        if (! class_exists(Payment::class)) {
            return false;
        }

        $payments = Payment::query()
            ->where('order_uuid', $order->uuid)
            ->get();

        return OrderFinancialStatus::fromPayments((int) $order->grand_total, $payments) === OrderFinancialStatus::PAID;
    }
}
