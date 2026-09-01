<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Controllers\Admin;

use Commerce\Marketplace\Models\Payout;
use Commerce\Marketplace\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PayoutController extends Controller
{
    public function __construct(private readonly PayoutService $payouts) {}

    public function index(): View
    {
        return view('marketplace::admin.payouts.index', [
            'items' => Payout::query()->with('seller')->latest()->paginate(25),
        ]);
    }

    public function markPaid(Request $request, Payout $payout): RedirectResponse
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payouts->markPaid($payout, $data['reference'] ?? null);

        return redirect()->route('admin.marketplace.payouts.index')->with('status', 'Payout marked as paid.');
    }
}
