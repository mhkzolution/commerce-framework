<?php

declare(strict_types=1);

namespace Commerce\Payment\Http\Controllers;

use Commerce\Contracts\Order\OrderQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Gateways\StripePaymentGateway;
use Commerce\Payment\Services\PaymentGatewayManager;
use Commerce\Payment\Services\PaymentQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StorefrontPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentQueryService $queryService,
        private readonly PaymentServiceInterface $paymentService,
        private readonly OrderQueryServiceInterface $orderQueryService,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function show(string $payment): View
    {
        $model = $this->queryService->findByUuid($payment);
        abort_if($model === null, 404);

        $gateway = $this->gatewayManager->driver();
        $initiation = $gateway->initiate($model, [
            'order' => $this->orderQueryService->findByUuid($model->order_uuid),
        ]);

        return view('payment::storefront.pay', [
            'payment' => $model,
            'order' => $this->orderQueryService->findByUuid($model->order_uuid),
            'gateway' => $gateway,
            'initiation' => $initiation,
        ]);
    }

    public function pay(string $payment): RedirectResponse
    {
        try {
            $model = $this->queryService->findByUuid($payment);

            if ($model === null) {
                throw new EntityNotFoundException("Payment [{$payment}] not found.");
            }

            $gateway = $this->gatewayManager->driver();
            $reference = null;

            if ($gateway->getCode() === 'simulated') {
                $initiation = $gateway->initiate($model);
                $reference = $initiation['reference'] ?? null;
            } elseif ($gateway instanceof StripePaymentGateway) {
                $reference = request()->string('payment_intent')->toString() ?: null;
            }

            $paid = $this->paymentService->markPaid($payment, $reference);
            $order = $this->orderQueryService->findByUuid($paid->order_uuid);

            return redirect()->route('storefront.checkout.confirmation', $order);
        } catch (DomainException|EntityNotFoundException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }

    public function fail(string $payment): RedirectResponse
    {
        try {
            $this->paymentService->markFailed($payment, 'Payment failed at gateway.');

            return redirect()->route('storefront.shop.index')->withErrors(['payment' => 'Payment failed. Your order was cancelled.']);
        } catch (DomainException|EntityNotFoundException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }
    }
}
