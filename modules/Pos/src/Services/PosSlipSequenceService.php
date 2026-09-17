<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Pos\Models\PosSlipSequence;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

final class PosSlipSequenceService extends BaseService
{
    public function __construct(
        private readonly PosSlipNumberGenerator $numbers,
        private readonly TenantContext $tenantContext,
    ) {}

    public function allocate(?DateTimeInterface $at = null, ?int $tenantId = null): string
    {
        $at = $at ?? now();
        $sequence = $this->next($at, $tenantId);

        return $this->numbers->format($at, $sequence);
    }

    public function next(?DateTimeInterface $at = null, ?int $tenantId = null): int
    {
        $day = Carbon::parse($at ?? now())->toDateString();
        $tenantKey = $tenantId ?? $this->tenantContext->id() ?? 0;

        return $this->retryOnContention(function () use ($day, $tenantKey): int {
            return DB::transaction(function () use ($day, $tenantKey): int {
                $sequence = $this->lockExisting($day, $tenantKey);

                if ($sequence === null) {
                    $sequence = $this->createCounter($day, $tenantKey)
                        ?? $this->lockExisting($day, $tenantKey);
                }

                if ($sequence === null) {
                    throw new DomainException('Unable to allocate a POS slip number.');
                }

                $sequence->increment('last_value');
                $sequence->refresh();

                return (int) $sequence->last_value;
            });
        });
    }

    private function lockExisting(string $day, int $tenantKey): ?PosSlipSequence
    {
        return PosSlipSequence::query()
            ->where('tenant_id', $tenantKey)
            ->whereDate('slip_date', $day)
            ->lockForUpdate()
            ->first();
    }

    private function createCounter(string $day, int $tenantKey): ?PosSlipSequence
    {
        try {
            return PosSlipSequence::query()->create([
                'tenant_id' => $tenantKey,
                'slip_date' => $day,
                'last_value' => 0,
            ]);
        } catch (QueryException) {
            return null;
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function retryOnContention(callable $callback): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                return $callback();
            } catch (Throwable $e) {
                $attempts++;

                if ($attempts >= 8 || ! $this->isContention($e)) {
                    throw $e;
                }

                usleep(25_000 * $attempts);
            }
        }
    }

    private function isContention(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'database is locked')
            || str_contains($message, 'UNIQUE constraint')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'Deadlock');
    }
}
