<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Orders\Models\Order;
use Commerce\Pos\Services\PosReceiptService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PosReceiptController extends Controller
{
    public function __construct(
        private readonly PosReceiptService $receiptService,
    ) {}

    public function show(string $orderUuid): View
    {
        $order = Order::query()->where('uuid', $orderUuid)->firstOrFail();
        $receipt = $this->receiptService->build($order);

        return view('pos::receipt.show', [
            'receipt' => $receipt,
        ]);
    }
}
