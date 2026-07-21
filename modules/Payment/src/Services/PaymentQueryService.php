<?php

declare(strict_types=1);

namespace Commerce\Payment\Services;

use Commerce\Contracts\Payment\PaymentQueryServiceInterface;
use Commerce\Contracts\Payment\PaymentStatus;
use Commerce\Core\Base\BaseQueryService;
use Commerce\Payment\Models\Payment;

final class PaymentQueryService extends BaseQueryService implements PaymentQueryServiceInterface
{
    public function findByUuid(string $uuid): ?object
    {
        return Payment::query()->where('uuid', $uuid)->first();
    }

    public function findPendingByOrderUuid(string $orderUuid): ?object
    {
        return Payment::query()
            ->where('order_uuid', $orderUuid)
            ->where('status', PaymentStatus::Pending->value)
            ->latest()
            ->first();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Payment>
     */
    public function paginate(?string $status = null, int $perPage = 25)
    {
        return Payment::query()
            ->when($status, static fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }
}
