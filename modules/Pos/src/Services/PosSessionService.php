<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Models\CashMovement;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;

final class PosSessionService
{
    public function activeSession(Register $register): ?Session
    {
        return Session::query()
            ->where('register_id', $register->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function openSession(
        Register $register,
        string $openedBy,
        int $openingBalance = 0,
        ?int $openedByUserId = null,
    ): Session {
        $existing = $this->activeSession($register);

        if ($existing !== null) {
            return $existing;
        }

        $session = Session::query()->create([
            'register_id' => $register->id,
            'opened_by' => $openedBy,
            'opened_by_user_id' => $openedByUserId,
            'opened_at' => now(),
            'opening_balance' => max(0, $openingBalance),
            'cash_sales_total' => 0,
            'status' => 'open',
        ]);

        if ($openingBalance > 0) {
            $this->recordMovement($session, CashMovement::TYPE_OPENING, $openingBalance, 'Opening float');
        }

        return $session;
    }

    public function closeSession(Session $session, string $closedBy, int $countedCash): Session
    {
        if ($session->status !== 'open') {
            throw new DomainException('Session is not open.');
        }

        $expectedCash = $this->expectedCash($session);
        $variance = $countedCash - $expectedCash;

        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $closedBy,
            'expected_cash' => $expectedCash,
            'counted_cash' => $countedCash,
            'variance' => $variance,
        ]);

        return $session->fresh();
    }

    public function recordCashSale(Session $session, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $session->increment('cash_sales_total', $amount);
        $this->recordMovement($session, CashMovement::TYPE_SALE, $amount);
    }

    public function recordMovement(Session $session, string $type, int $amount, ?string $note = null): CashMovement
    {
        return CashMovement::query()->create([
            'session_id' => $session->id,
            'type' => $type,
            'amount' => $amount,
            'note' => $note,
        ]);
    }

    public function expectedCash(Session $session): int
    {
        $session->loadMissing('cashMovements');

        $payIns = (int) $session->cashMovements
            ->where('type', CashMovement::TYPE_PAY_IN)
            ->sum('amount');

        $payOuts = (int) $session->cashMovements
            ->where('type', CashMovement::TYPE_PAY_OUT)
            ->sum('amount');

        return (int) $session->opening_balance
            + (int) $session->cash_sales_total
            + $payIns
            - $payOuts;
    }
}
