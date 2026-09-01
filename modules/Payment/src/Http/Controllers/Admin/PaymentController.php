<?php

declare(strict_types=1);

namespace Commerce\Payment\Http\Controllers\Admin;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Services\PaymentQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentQueryService $queryService,
        private readonly OrderQueryServiceInterface $orderQueryService,
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    public function index(Request $request): View
    {
        return view('payment::admin.index', [
            'payments' => $this->queryService->paginate(
                status: $request->string('status')->toString() ?: null,
            ),
            'statuses' => config('payment.statuses', []),
        ]);
    }

    public function show(string $payment): View
    {
        $model = $this->queryService->findByUuid($payment);
        abort_if($model === null, 404);

        return view('payment::admin.show', [
            'payment' => $model,
            'order' => $this->orderQueryService->findByUuid($model->order_uuid),
            'statuses' => config('payment.statuses', []),
        ]);
    }

    public function refund(Request $request, string $payment): RedirectResponse
    {
        $model = $this->queryService->findByUuid($payment);
        abort_if($model === null, 404);

        $amount = $request->filled('amount') ? (int) $request->input('amount') : null;

        $this->paymentService->refund($payment, $amount);

        return redirect()
            ->route('admin.payments.show', $payment)
            ->with('status', 'Payment refunded.');
    }
}
