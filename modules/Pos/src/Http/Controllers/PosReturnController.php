<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Contracts\Payment\PaymentStatus;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Services\OrderQueryService;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PosReturnController extends Controller
{
    public function __construct(
        private readonly OrderQueryService $orderQueryService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $order = null;
        $payment = null;
        $error = null;

        if ($search !== '') {
            $order = $this->orderQueryService->findByOrderNumber($search);

            if ($order === null) {
                $order = $this->orderQueryService->findByUuid($search);
            }

            if ($order === null) {
                $error = 'ไม่พบคำสั่งซื้อ';
            } elseif ($order->channel !== 'pos') {
                $error = 'คำสั่งซื้อนี้ไม่ใช่รายการจากหน้าร้าน';
                $order = null;
            } else {
                $payment = Payment::query()
                    ->where('order_uuid', $order->uuid)
                    ->latest()
                    ->first();
            }
        }

        return view('pos::pos.returns.index', [
            'search' => $search,
            'order' => $order,
            'payment' => $payment,
            'error' => $error,
            'statuses' => config('orders.statuses', []),
            'paymentStatuses' => config('payment.statuses', []),
        ]);
    }

    public function refund(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_uuid' => ['required', 'uuid'],
        ]);

        /** @var Order|null $order */
        $order = $this->orderQueryService->findByUuid($data['order_uuid']);

        abort_if($order === null || $order->channel !== 'pos', 404);

        if (! app()->bound(PaymentServiceInterface::class)) {
            return redirect()
                ->route('pos.returns.index', ['search' => $order->order_number])
                ->withErrors(['refund' => 'ระบบชำระเงินยังไม่พร้อมใช้งาน']);
        }

        $payment = Payment::query()
            ->where('order_uuid', $order->uuid)
            ->where('status', PaymentStatus::Paid->value)
            ->latest()
            ->first();

        if ($payment === null) {
            return redirect()
                ->route('pos.returns.index', ['search' => $order->order_number])
                ->withErrors(['refund' => 'ไม่พบการชำระเงินที่คืนได้']);
        }

        try {
            app(PaymentServiceInterface::class)->refund($payment->uuid);
        } catch (DomainException $exception) {
            return redirect()
                ->route('pos.returns.index', ['search' => $order->order_number])
                ->withErrors(['refund' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.returns.index', ['search' => $order->order_number])
            ->with('status', 'คืนเงินสำเร็จ');
    }
}
