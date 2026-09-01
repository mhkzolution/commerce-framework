<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Inventory\Mail\PurchaseOrderPdfMail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PurchaseOrderFailedJobService
{
    /**
     * @return LengthAwarePaginator<int, object{
     *     id: int,
     *     uuid: string,
     *     queue: string,
     *     failed_at: string,
     *     summary: string,
     *     exception: string
     * }>
     */
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        $paginator = DB::table('failed_jobs')
            ->where('payload', 'like', '%'.str_replace('\\', '\\\\', PurchaseOrderPdfMail::class).'%')
            ->orderByDesc('failed_at')
            ->paginate($perPage);

        return $paginator->through(function (object $row): object {
            return (object) [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'queue' => (string) $row->queue,
                'failed_at' => (string) $row->failed_at,
                'summary' => $this->summary((string) $row->payload),
                'exception' => Str::of((string) $row->exception)->before("\n")->toString(),
            ];
        });
    }

    public function retry(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    public function forget(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
    }

    public function count(): int
    {
        return (int) DB::table('failed_jobs')
            ->where('payload', 'like', '%'.str_replace('\\', '\\\\', PurchaseOrderPdfMail::class).'%')
            ->count();
    }

    private function summary(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'Purchase order email';
        }

        $displayName = (string) ($decoded['displayName'] ?? 'Purchase order email');

        if (preg_match('/reference";s:\d+:"([^"]+)"/', $payload, $matches) === 1) {
            return $displayName.' · '.$matches[1];
        }

        return $displayName;
    }
}
