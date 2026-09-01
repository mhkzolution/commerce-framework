<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Controllers\Admin;

use Commerce\Inventory\Services\PurchaseOrderFailedJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PurchaseOrderFailedJobController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderFailedJobService $failedJobs,
    ) {}

    public function index(): View
    {
        return view('inventory::admin.purchase-orders.failed-jobs', [
            'failedJobs' => $this->failedJobs->paginate(),
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        $this->failedJobs->retry($uuid);

        return redirect()
            ->route('admin.inventory.purchase-orders.failed-jobs.index')
            ->with('status', 'Failed job queued for retry.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $this->failedJobs->forget($uuid);

        return redirect()
            ->route('admin.inventory.purchase-orders.failed-jobs.index')
            ->with('status', 'Failed job removed.');
    }
}
