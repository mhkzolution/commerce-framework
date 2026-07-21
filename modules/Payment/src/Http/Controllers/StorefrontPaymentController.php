<?php

declare(strict_types=1);

namespace Commerce\Payment\Http\Controllers;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Services\PaymentQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StorefrontPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentQueryService $queryService,
        private readonly PaymentServiceInterface $paymentService,
        private readonly OrderQueryServiceInterface $orderQueryService,
    ) {}

    public function show(string $payment): View
    {
        $model = $this->queryService->findByUuid($payment);
        abort_if($model === null, 404);

        return view('payment::storefront.pay', [
            'payment' => $model,
            'order' => $this->orderQueryService->findByUuid($model->order_uuid),
        ]);
    }

    public function pay(string $payment): RedirectResponse
    {
        try {
            $paid = $this->paymentService->markPaid($payment);
            $order = $this->orderQueryService->findByUuid($paid->order_uuid);

            return redirect()->route('storefront.checkout.confirmation', $order);
        } catch (DomainException|EntityNotFoundException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function fail(string $payment): RedirectResponse
    {
        try {
            $this->paymentService->markFailed($payment, 'Simulated payment failure');

            return redirect()->route('storefront.shop.index')->withErrors(['payment' => 'Payment failed. Your order was cancelled.']);
        } catch (DomainException|EntityNotFoundException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }
}
