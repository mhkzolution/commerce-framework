<?php

declare(strict_types=1);

namespace Commerce\Inventory\Mail;

use Commerce\Inventory\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PurchaseOrderPdfMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly PurchaseOrder $order,
        public readonly string $pdfContent,
        public readonly string $mailSubject,
        public readonly string $markdownView,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->markdownView,
            with: [
                'order' => $this->order,
                'supplierName' => $this->order->supplier?->name ?? $this->order->supplier_name ?? 'Supplier',
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfContent, str($this->order->reference)->slug().'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
