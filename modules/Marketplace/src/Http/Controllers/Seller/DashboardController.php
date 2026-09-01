<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Controllers\Seller;

use Commerce\Marketplace\Models\Commission;
use Commerce\Marketplace\Models\Payout;
use Commerce\Marketplace\Models\Seller;
use Commerce\Marketplace\Services\PayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(private readonly PayoutService $payouts) {}

    public function index(Request $request): View
    {
        /** @var Seller $seller */
        $seller = $request->attributes->get('seller');

        return view('marketplace::seller.dashboard', [
            'seller' => $seller,
            'availableBalance' => $this->payouts->availableBalance($seller),
            'recentCommissions' => Commission::query()
                ->where('seller_uuid', $seller->uuid)
                ->latest()
                ->limit(10)
                ->get(),
            'recentPayouts' => Payout::query()
                ->where('seller_uuid', $seller->uuid)
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function commissions(Request $request): View
    {
        /** @var Seller $seller */
        $seller = $request->attributes->get('seller');

        return view('marketplace::seller.commissions', [
            'seller' => $seller,
            'items' => Commission::query()
                ->where('seller_uuid', $seller->uuid)
                ->latest()
                ->paginate(25),
        ]);
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        /** @var Seller $seller */
        $seller = $request->attributes->get('seller');

        $this->payouts->requestPayout($seller);

        return redirect()->route('seller.dashboard')->with('status', 'Payout requested.');
    }
}
