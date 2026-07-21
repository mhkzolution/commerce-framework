<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Commerce\Contracts\Admin\AdminGlobalSearchServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, AdminGlobalSearchServiceInterface $search): JsonResponse
    {
        return response()->json([
            'results' => $search->search(
                query: $request->string('q')->toString(),
                user: $request->user(),
            ),
        ]);
    }
}
