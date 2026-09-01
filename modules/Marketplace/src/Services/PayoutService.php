<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Services;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Marketplace\Models\Commission;
use Commerce\Marketplace\Models\Payout;
use Commerce\Marketplace\Models\Seller;
use Illuminate\Support\Facades\DB;

final class PayoutService
{
    public function availableBalance(Seller $seller): int
    {
        return (int) Commission::query()
            ->where('seller_uuid', $seller->uuid)
            ->where('status', 'pending')
            ->whereNull('payout_uuid')
            ->sum('commission_amount');
    }

    public function requestPayout(Seller $seller): Payout
    {
        return DB::transaction(function () use ($seller): Payout {
            $commissions = Commission::query()
                ->where('seller_uuid', $seller->uuid)
                ->where('status', 'pending')
                ->whereNull('payout_uuid')
                ->lockForUpdate()
                ->get();

            if ($commissions->isEmpty()) {
                throw new DomainException('No pending commissions available for payout.');
            }

            $amount = (int) $commissions->sum('commission_amount');

            $payout = Payout::query()->create([
                'seller_uuid' => $seller->uuid,
                'amount' => $amount,
                'status' => 'pending',
            ]);

            Commission::query()
                ->whereIn('id', $commissions->pluck('id'))
                ->update(['payout_uuid' => $payout->uuid]);

            return $payout->fresh(['seller']);
        });
    }

    public function markPaid(Payout $payout, ?string $reference = null): Payout
    {
        if ($payout->status !== 'pending') {
            throw new DomainException('Only pending payouts can be marked as paid.');
        }

        return DB::transaction(function () use ($payout, $reference): Payout {
            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
                'reference' => $reference,
            ]);

            Commission::query()
                ->where('payout_uuid', $payout->uuid)
                ->update(['status' => 'paid']);

            return $payout->fresh(['seller']);
        });
    }
}
