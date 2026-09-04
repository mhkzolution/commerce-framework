<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PosOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $orders = Order::query()
            ->with('lineItems')
            ->where('channel', 'pos')
            ->when($status !== '', static fn ($query) => $query->where('status', $status))
            ->when($search !== '', static function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('pos::pos.orders.index', [
            'orders' => $orders,
            'statuses' => config('orders.statuses', []),
            'search' => $search,
            'status' => $status,
        ]);
    }
}
