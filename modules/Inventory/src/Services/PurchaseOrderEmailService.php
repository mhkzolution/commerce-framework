<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Inventory\Mail\PurchaseOrderPdfMail;
use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Support\PurchaseOrderMailTemplate;
use Illuminate\Support\Facades\Mail;

final class PurchaseOrderEmailService
{
    public function __construct(
        private readonly PurchaseOrderPdfService $pdfService,
        private readonly PurchaseOrderMailTemplate $mailTemplate,
    ) {}

    /**
     * @param  array<string, array{variant: mixed, product_name: string|null}>  $variantContext
     */
    public function send(PurchaseOrder $order, string $email, array $variantContext): void
    {
        if ($email === '') {
            throw new DomainException('Supplier email is required.');
        }

        $mailable = $this->mailable($order, $variantContext);
        $pending = Mail::to($email);

        if (config('inventory.purchase_order.queue_emails', true)) {
            $pending->queue(
                $mailable->onQueue((string) config('inventory.purchase_order.queue_name', 'notifications')),
            );

            return;
        }

        $pending->send($mailable);
    }

    /**
     * @param  array<string, array{variant: mixed, product_name: string|null}>  $variantContext
     */
    public function sendIfPossible(PurchaseOrder $order, ?string $email, array $variantContext): bool
    {
        if ($email === null || $email === '') {
            return false;
        }

        $this->send($order, $email, $variantContext);

        return true;
    }

    /**
     * @param  array<string, array{variant: mixed, product_name: string|null}>  $variantContext
     */
    private function mailable(PurchaseOrder $order, array $variantContext): PurchaseOrderPdfMail
    {
        $template = $this->mailTemplate->resolve($order);

        return new PurchaseOrderPdfMail(
            $order,
            $this->pdfService->render($order, $variantContext),
            $template['subject'],
            $template['view'],
        );
    }
}
