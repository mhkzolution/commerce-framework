<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Controllers\Admin;

use Commerce\Marketplace\Models\Commission;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CommissionController extends Controller
{
    public function index(): View
    {
        return view('marketplace::admin.commissions.index', [
            'items' => Commission::query()
                ->with('seller')
                ->latest()
                ->paginate(25),
        ]);
    }
}
