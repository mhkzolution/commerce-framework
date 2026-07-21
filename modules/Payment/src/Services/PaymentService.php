<?php

declare(strict_types=1);

namespace Commerce\Payment\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Contracts\Order\OrderStatus;
use Commerce\Contracts\Payment\PaymentStatus;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\Models\Order;
use Commerce\Payment\Contracts\PaymentServiceInterface;
use Commerce\Payment\Events\PaymentFailed;
use Commerce\Payment\Events\PaymentPaid;
use Commerce\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

final class PaymentService extends BaseService implements PaymentServiceInterface
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function createForOrder(string $orderUuid, int $amount, string $currency): Payment
    {
        return Payment::query()->create([
            'order_uuid' => $orderUuid,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending->value,
            'method' => (string) config('payment.default_method', 'manual'),
        ]);
    }

    public function markPaid(string $uuid, ?string $gatewayReference = null): Payment
    {
        return DB::transaction(function () use ($uuid, $gatewayReference): Payment {
            $payment = $this->findOrFail($uuid);

            if (! $payment->isPending()) {
                throw new DomainException('Only pending payments can be marked as paid.');
            }

            $payment->update([
                'status' => PaymentStatus::Paid->value,
                'gateway_reference' => $gatewayReference ?? ('SIM-' . strtoupper(substr($payment->uuid, 0, 8))),
                'paid_at' => now(),
            ]);

            $payment = $payment->fresh();

            if (config('payment.confirm_order_on_payment', true)) {
                $this->confirmOrderIfPending($payment->order_uuid);
            }

            $this->eventBus->dispatchReliable(new PaymentPaid(
                paymentUuid: $payment->uuid,
                orderUuid: $payment->order_uuid,
                amount: (int) $payment->amount,
                currency: $payment->currency,
                gatewayReference: $payment->gateway_reference,
                tenantId: $payment->tenant_id,
            ));

            return $payment;
        });
    }

    public function markFailed(string $uuid, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($uuid, $reason): Payment {
            $payment = $this->findOrFail($uuid);

            if (! $payment->isPending()) {
                throw new DomainException('Only pending payments can be marked as failed.');
            }

            $payment->update([
                'status' => PaymentStatus::Failed->value,
                'failed_at' => now(),
                'meta' => array_merge($payment->meta ?? [], ['failure_reason' => $reason]),
            ]);

            $payment = $payment->fresh();

            if (config('payment.cancel_order_on_payment_failure', true)) {
                $this->cancelOrderIfPending($payment->order_uuid);
            }

            $this->eventBus->dispatch(new PaymentFailed(
                paymentUuid: $payment->uuid,
                orderUuid: $payment->order_uuid,
                amount: (int) $payment->amount,
                currency: $payment->currency,
                reason: $reason,
                tenantId: $payment->tenant_id,
            ));

            return $payment;
        });
    }

    private function confirmOrderIfPending(string $orderUuid): void
    {
        $order = Order::query()->where('uuid', $orderUuid)->first();

        if ($order === null || $order->status !== OrderStatus::Pending->value) {
            return;
        }

        $this->orderService->confirm($orderUuid);
    }

    private function cancelOrderIfPending(string $orderUuid): void
    {
        $order = Order::query()->where('uuid', $orderUuid)->first();

        if ($order === null || $order->status !== OrderStatus::Pending->value) {
            return;
        }

        $this->orderService->cancel($orderUuid);
    }

    private function findOrFail(string $uuid): Payment
    {
        $payment = Payment::query()->where('uuid', $uuid)->first();

        if ($payment === null) {
            throw new EntityNotFoundException("Payment [{$uuid}] not found.");
        }

        return $payment;
    }
}
