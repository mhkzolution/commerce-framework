<?php

declare(strict_types=1);

namespace Commerce\Payment\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Http\Resources\PaymentResource;
use Commerce\Payment\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PaymentApiController extends Controller
{
    public function __construct(
        private readonly PaymentQueryServiceInterface $queryService,
        private readonly PaymentServiceInterface $paymentService,
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function config(): JsonResponse
    {
        $driver = $this->gatewayManager->driver();
        $enabled = $this->gatewayManager->enabled();

        return ApiResponse::success([
            'gateway' => $driver->getCode(),
            'gateway_name' => $driver->getName(),
            'gateways' => array_map(
                static fn (object $gateway): array => [
                    'code' => $gateway->getCode(),
                    'name' => $gateway->getName(),
                ],
                $enabled,
            ),
            'publishable_key' => $driver->getCode() === 'stripe'
                ? config('payment.stripe.publishable_key')
                : null,
            'simulate_enabled' => $this->gatewayManager->driver('simulated')->isEnabled(),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $payment = $this->queryService->findByUuid($uuid);

        if ($payment === null) {
            return ApiResponse::error('payment.not_found', 'Payment not found.', status: 404);
        }

        return ApiResponse::success(new PaymentResource($payment));
    }

    public function initiate(string $uuid): JsonResponse
    {
        try {
            $payment = $this->queryService->findByUuid($uuid);

            if ($payment === null) {
                return ApiResponse::error('payment.not_found', 'Payment not found.', status: 404);
            }

            $driver = $this->gatewayManager->driver((string) $payment->method);
            $initiation = $driver->initiate($payment);

            if (! empty($initiation['reference'])) {
                $payment->update(['gateway_reference' => $initiation['reference']]);
                $payment = $payment->fresh();
            }

            return ApiResponse::success([
                'payment' => new PaymentResource($payment),
                'initiation' => $initiation,
            ]);
        } catch (\Throwable $exception) {
            return ApiResponse::error('payment.initiate_failed', $exception->getMessage(), status: 422);
        }
    }

    public function pay(string $uuid): JsonResponse
    {
        try {
            $payment = $this->queryService->findByUuid($uuid);

            if ($payment === null) {
                return ApiResponse::error('payment.not_found', 'Payment not found.', status: 404);
            }

            $driver = $this->gatewayManager->driver((string) $payment->method);

            if ($driver->getCode() !== 'simulated' || ! $driver->isEnabled()) {
                return ApiResponse::error(
                    'payment.use_initiate',
                    'Use POST /payments/{uuid}/initiate for gateway payments.',
                    status: 422,
                );
            }

            $initiation = $driver->initiate($payment);
            $payment = $this->paymentService->markPaid($uuid, $initiation['reference'] ?? null);

            return ApiResponse::success(new PaymentResource($payment));
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('payment.failed', $exception->getMessage(), status: 422);
        }
    }

    public function refund(Request $request, string $uuid): JsonResponse
    {
        try {
            $amount = $request->filled('amount') ? (int) $request->input('amount') : null;
            $payment = $this->paymentService->refund($uuid, $amount);

            return ApiResponse::success(new PaymentResource($payment));
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('payment.refund_failed', $exception->getMessage(), status: 422);
        }
    }
}
