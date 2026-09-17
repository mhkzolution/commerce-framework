<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\DocumentSequence;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DocumentSequenceService extends BaseService
{
    public function __construct(
        private readonly DocumentNumberGenerator $numbers,
        private readonly TenantContext $tenantContext,
    ) {}

    public function allocate(DocumentType $type, ?DateTimeInterface $at = null, ?int $tenantId = null): string
    {
        $period = Carbon::parse($at ?? now())->format('Ym');
        $sequence = $this->next($type, $at, $tenantId);

        return $this->numbers->format($type, $period, $sequence);
    }

    public function next(DocumentType $type, ?DateTimeInterface $at = null, ?int $tenantId = null): int
    {
        $period = Carbon::parse($at ?? now())->format('Ym');
        $tenantKey = $tenantId ?? $this->tenantContext->id() ?? 0;

        return $this->retryOnContention(function () use ($type, $period, $tenantKey): int {
            return DB::transaction(function () use ($type, $period, $tenantKey): int {
                $sequence = $this->lockExisting($type, $period, $tenantKey);

                if ($sequence === null) {
                    $sequence = $this->createCounter($type, $period, $tenantKey)
                        ?? $this->lockExisting($type, $period, $tenantKey);
                }

                if ($sequence === null) {
                    throw new DomainException('Unable to allocate a document sequence.');
                }

                $sequence->increment('last_value');
                $sequence->refresh();

                return (int) $sequence->last_value;
            });
        });
    }

    private function lockExisting(DocumentType $type, string $period, int $tenantKey): ?DocumentSequence
    {
        return DocumentSequence::query()
            ->where('tenant_id', $tenantKey)
            ->where('type', $type->value)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();
    }

    private function createCounter(DocumentType $type, string $period, int $tenantKey): ?DocumentSequence
    {
        try {
            return DocumentSequence::query()->create([
                'tenant_id' => $tenantKey,
                'type' => $type->value,
                'period' => $period,
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
