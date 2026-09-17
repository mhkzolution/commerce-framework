<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Enums\PaperWidth;
use Commerce\Pos\Services\PosPrintJobService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PosReceiptController extends Controller
{
    public function __construct(
        private readonly PosPrintJobService $printJobs,
    ) {}

    public function show(Request $request, string $orderUuid): View
    {
        $order = Order::query()->where('uuid', $orderUuid)->firstOrFail();

        try {
            $job = $this->printJobs->createSlip($order, PaperWidth::fromQuery($request->query('paper_width')));
        } catch (DomainException $exception) {
            abort(422, $exception->getMessage());
        }

        return $this->printJobs->render($job);
    }
}
