<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Http\Controllers;

use Commerce\WarehouseScanner\Models\WarehouseScan;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class HistoryController extends Controller
{
    public function index(): View
    {
        $scans = WarehouseScan::query()
            ->with('user:id,name')
            ->latest()
            ->paginate(50);

        return view('warehouse::scanner.history', [
            'scans' => $scans,
        ]);
    }
}
