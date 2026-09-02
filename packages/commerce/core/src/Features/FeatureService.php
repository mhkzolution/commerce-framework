<?php

declare(strict_types=1);

namespace Commerce\Core\Features;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Events\SystemFeatureStatusChanged;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

final class FeatureService extends BaseService
{
    public const CACHE_KEY = 'commerce.system_features';

    public const CACHE_TTL_SECONDS = 3600;

    /** @var Collection<int, SystemFeature>|null */
    private ?Collection $memoized = null;

    private ?bool $registryAvailable = null;

    /** @var array<string, true> */
    private array $warnedUnknown = [];

    /** @var array<string, true> */
    private array $warnedParentMissing = [];

    public function __construct(private readonly EventBusInterface $events) {}

    public static function enabled(string $code): bool
    {
        try {
            $service = self::instance();
            $feature = $service->find($code, warnUnknown: true);

            if (! $service->registryAvailable) {
                Log::warning('System feature registry unavailable.');

                return false;
            }

            if ($feature === null) {
                return false;
            }

            $module = ModuleService::get($feature->module_code);

            if ($module === null) {
                $service->warnParentMissing($feature);

                return false;
            }

            if (ModuleService::isDisabled($feature->module_code)) {
                return false;
            }

            if ($feature->is_core) {
                return true;
            }

            return $feature->status === FeatureStatus::Enabled;
        } catch (Throwable) {
            Log::warning('System feature registry unavailable.');

            return false;
        }
    }

    public static function get(string $code): ?SystemFeature
    {
        return self::instance()->find($code, warnUnknown: false);
    }

    /**
     * @return Collection<int, SystemFeature>
     */
    public static function all(): Collection
    {
        return self::instance()->definitions();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        if (app()->bound(self::class)) {
            app(self::class)->resetMemo();
        }
    }

    private function find(string $code, bool $warnUnknown = false): ?SystemFeature
    {
        $feature = $this->definitions()->firstWhere('code', $code);

        if ($feature === null && $warnUnknown && $this->registryAvailable) {
            $this->warnUnknown($code);
        }

        return $feature;
    }

    /**
     * @return Collection<int, SystemFeature>
     */
    public function definitions(): Collection
    {
        if ($this->memoized !== null) {
            return $this->memoized;
        }

        try {
            /** @var array{available: bool, rows: list<array<string, mixed>>}|list<array<string, mixed>> $payload */
            $payload = Cache::remember(
                self::CACHE_KEY,
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                static function (): array {
                    if (! Schema::hasTable('system_features')) {
                        return ['available' => false, 'rows' => []];
                    }

                    return [
                        'available' => true,
                        'rows' => SystemFeature::query()
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->get()
                            ->map(static fn (SystemFeature $feature): array => $feature->getAttributes())
                            ->all(),
                    ];
                },
            );

            if (array_is_list($payload)) {
                $this->registryAvailable = true;
                $rows = $payload;
            } else {
                $this->registryAvailable = $payload['available'];
                $rows = $payload['rows'];
            }
        } catch (Throwable) {
            $this->registryAvailable = false;
            $this->memoized = collect();

            return $this->memoized;
        }

        if (! $this->registryAvailable) {
            $this->memoized = collect();

            return $this->memoized;
        }

        $this->memoized = collect($rows)
            ->map(static function (array $attributes): SystemFeature {
                $feature = new SystemFeature;
                $feature->setRawAttributes($attributes, true);
                $feature->exists = true;

                return $feature;
            })
            ->values();

        return $this->memoized;
    }

    public function updateStatus(SystemFeature $feature, FeatureStatus $status): SystemFeature
    {
        if ($feature->is_core) {
            throw ValidationException::withMessages([
                'status' => ['Core features cannot be disabled.'],
            ]);
        }

        $oldStatus = $feature->status;

        if ($oldStatus === $status) {
            return $feature;
        }

        $feature->update(['status' => $status]);
        $fresh = $feature->fresh() ?? $feature;

        $this->events->dispatch(new SystemFeatureStatusChanged(
            feature: $fresh,
            oldStatus: $oldStatus,
            newStatus: $fresh->status,
            moduleName: ModuleService::get($fresh->module_code)?->name,
            userId: self::actorId(),
        ));

        return $fresh;
    }

    public function resetMemo(): void
    {
        $this->memoized = null;
        $this->registryAvailable = null;
    }

    private function warnUnknown(string $code): void
    {
        if (isset($this->warnedUnknown[$code])) {
            return;
        }

        $this->warnedUnknown[$code] = true;

        Log::warning('Unknown system feature requested.', [
            'warning_code' => 'feature_unknown',
            'code' => $code,
        ]);
    }

    private function warnParentMissing(SystemFeature $feature): void
    {
        if (isset($this->warnedParentMissing[$feature->code])) {
            return;
        }

        $this->warnedParentMissing[$feature->code] = true;

        Log::warning('System feature parent module is missing.', [
            'warning_code' => 'feature_parent_missing',
            'code' => $feature->code,
            'module_code' => $feature->module_code,
        ]);
    }

    private static function actorId(): ?int
    {
        $id = Auth::id();

        return is_numeric($id) ? (int) $id : null;
    }

    private static function instance(): self
    {
        return app(self::class);
    }
}
