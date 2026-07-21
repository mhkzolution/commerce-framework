<?php

declare(strict_types=1);

namespace Commerce\Payment\Http\Controllers;

use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Gateways\StripePaymentGateway;
use Commerce\Payment\Services\PaymentGatewayManager;
use Commerce\Payment\Services\PaymentQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly PaymentQueryService $queryService,
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $driver = $this->gatewayManager->driver($gateway);

        if ($driver instanceof StripePaymentGateway) {
            $payload = $request->getContent();
            $signature = $request->header('Stripe-Signature');

            if (! $driver->verifyWebhookSignature($payload, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            $paymentUuid = $driver->handleWebhook($data, $signature);

            if ($paymentUuid === null) {
                return response()->json(['received' => true]);
            }

            $payment = $this->queryService->findByUuid($paymentUuid);

            if ($payment === null) {
                return response()->json(['error' => 'Payment not found'], 404);
            }

            if (($data['type'] ?? '') === 'payment_intent.succeeded') {
                $this->paymentService->markPaid($paymentUuid, $data['data']['object']['id'] ?? null);
            } elseif (($data['type'] ?? '') === 'payment_intent.payment_failed') {
                $this->paymentService->markFailed($paymentUuid, $data['data']['object']['last_payment_error']['message'] ?? 'Stripe payment failed');
            }

            return response()->json(['received' => true]);
        }

        $paymentUuid = $driver->handleWebhook($request->all(), $request->header('X-Signature'));

        if ($paymentUuid !== null) {
            $this->paymentService->markPaid($paymentUuid);
        }

        return response()->json(['received' => true]);
    }
}
