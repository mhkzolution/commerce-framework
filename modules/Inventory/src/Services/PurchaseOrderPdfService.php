<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

final class PurchaseOrderPdfService
{
    public function __construct(
        private readonly PurchaseOrderMoney $money,
    ) {}

    /**
     * @param  array<string, array{variant: mixed, product_name: string|null}>  $variantContext
     */
    public function render(PurchaseOrder $order, array $variantContext): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('inventory::admin.purchase-orders.pdf', [
            'order' => $order,
            'variantContext' => $variantContext,
            'money' => $this->money,
        ])->render());
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param  array<string, array{variant: mixed, product_name: string|null}>  $variantContext
     */
    public function download(PurchaseOrder $order, array $variantContext): Response
    {
        $filename = str($order->reference)->slug().'.pdf';

        return response($this->render($order, $variantContext), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
