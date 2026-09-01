<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Orders\Services\OrderQueryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PosOrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryService $orderQueryService,
    ) {}

    public function index(Request $request): View
    {
        return view('pos::pos.orders.index', [
            'orders' => $this->orderQueryService->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
                perPage: 20,
                relations: ['lineItems'],
                channel: 'pos',
            ),
            'statuses' => config('orders.statuses', []),
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
        ]);
    }
}
