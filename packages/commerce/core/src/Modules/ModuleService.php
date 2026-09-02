<?php

declare(strict_types=1);

namespace Commerce\Core\Modules;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Events\SystemModuleStatusChanged;
use Commerce\Core\Models\SystemModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ModuleService extends BaseService
{
    public const CACHE_KEY = 'commerce.system_modules';

    public const CACHE_TTL_SECONDS = 3600;

    /** @var Collection<int, SystemModule>|null */
    private ?Collection $memoized = null;

    /** @var array<string, true> */
    private array $warnedUnknown = [];

    public function __construct(private readonly EventBusInterface $events) {}

    public static function isActive(string $code): bool
    {
        return self::instance()->statusIs($code, ModuleStatus::Active);
    }

    public static function isHidden(string $code): bool
    {
        return self::instance()->statusIs($code, ModuleStatus::Hidden);
    }

    public static function isDisabled(string $code): bool
    {
        return self::instance()->statusIs($code, ModuleStatus::Disabled);
    }

    public static function get(string $code): ?SystemModule
    {
        return self::instance()->find($code);
    }

    /**
     * @return Collection<int, SystemModule>
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

    public function find(string $code, bool $warnUnknown = false): ?SystemModule
    {
        $module = $this->definitions()->firstWhere('code', $code);

        if ($module === null && $warnUnknown) {
            $this->warnUnknown($code);
        }

        return $module;
    }

    /**
     * @return Collection<int, SystemModule>
     */
    public function definitions(): Collection
    {
        if ($this->memoized !== null) {
            return $this->memoized;
        }

        try {
            /** @var list<array<string, mixed>> $rows */
            $rows = Cache::remember(
                self::CACHE_KEY,
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                function (): array {
                    if (! Schema::hasTable('system_modules')) {
                        return [];
                    }

                    return SystemModule::query()
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get()
                        ->map(static fn (SystemModule $module): array => $module->getAttributes())
                        ->all();
                },
            );
        } catch (Throwable $exception) {
            Log::warning('System module registry unavailable.', [
                'exception' => $exception->getMessage(),
            ]);

            $this->memoized = collect();

            return $this->memoized;
        }

        $this->memoized = collect($rows)
            ->map(static function (array $attributes): SystemModule {
                $module = new SystemModule;
                $module->setRawAttributes($attributes, true);
                $module->exists = true;

                return $module;
            })
            ->values();

        return $this->memoized;
    }

    public function updateStatus(SystemModule $module, ModuleStatus $status): SystemModule
    {
        if ($module->is_core) {
            throw ValidationException::withMessages([
                'status' => [__('commerce::admin.module_core_locked')],
            ]);
        }

        $oldStatus = $module->status;

        if ($oldStatus === $status) {
            return $module;
        }

        $module->update(['status' => $status]);
        $fresh = $module->fresh() ?? $module;

        $this->events->dispatch(new SystemModuleStatusChanged(
            module: $fresh,
            oldStatus: $oldStatus,
            newStatus: $fresh->status,
            userId: self::actorId(),
        ));

        return $fresh;
    }

    public function resetMemo(): void
    {
        $this->memoized = null;
    }

    private function statusIs(string $code, ModuleStatus $status): bool
    {
        $module = $this->find($code, warnUnknown: true);

        if ($module === null) {
            return false;
        }

        if ($module->is_core) {
            return $status === ModuleStatus::Active;
        }

        return $module->status === $status;
    }

    private function warnUnknown(string $code): void
    {
        if ($code === '' || isset($this->warnedUnknown[$code])) {
            return;
        }

        $this->warnedUnknown[$code] = true;

        Log::warning('Unknown system module requested.', ['code' => $code]);
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
