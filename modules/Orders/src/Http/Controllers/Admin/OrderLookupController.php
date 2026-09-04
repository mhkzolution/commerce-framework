<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Controllers\Admin;

use Commerce\Orders\Services\AdminOrderLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class OrderLookupController extends Controller
{
    public function __construct(
        private readonly AdminOrderLookupService $lookup,
    ) {}

    public function customers(Request $request): JsonResponse
    {
        return response()->json([
            'results' => $this->lookup->customers($request->string('q')->toString()),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json([
            'results' => $this->lookup->products($request->string('q')->toString()),
        ]);
    }
}
