<?php

declare(strict_types=1);

namespace Commerce\Inventory\Support;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Notification\Services\NotificationTemplateService;

final class PurchaseOrderMailTemplate
{
    /**
     * @return array{subject: string, view: string}
     */
    public function resolve(PurchaseOrder $order): array
    {
        $variables = $this->variables($order);

        if (class_exists(NotificationTemplateService::class)) {
            $template = app(NotificationTemplateService::class)
                ->findByCode('purchase_order.supplier');

            if ($template !== null && filled($template->view)) {
                return [
                    'subject' => $this->render((string) $template->subject, $variables),
                    'view' => (string) $template->view,
                ];
            }
        }

        /** @var array{subject?: string, view?: string} $config */
        $config = config('notification.templates.purchase_order.supplier', [
            'subject' => 'Purchase Order {{reference}}',
            'view' => 'inventory::mail.purchase-order',
        ]);

        return [
            'subject' => $this->render((string) ($config['subject'] ?? 'Purchase Order {{reference}}'), $variables),
            'view' => (string) ($config['view'] ?? 'inventory::mail.purchase-order'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function variables(PurchaseOrder $order): array
    {
        return [
            'reference' => $order->reference,
            'supplier_name' => $order->supplier?->name ?? $order->supplier_name ?? 'Supplier',
            'expected_at' => $order->expected_at?->format('M j, Y') ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function render(string $template, array $variables): string
    {
        $rendered = $template;

        foreach ($variables as $key => $value) {
            $rendered = str_replace('{{'.$key.'}}', $value, $rendered);
        }

        return $rendered;
    }
}
