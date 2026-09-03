<?php

declare(strict_types=1);

namespace Commerce\Settings\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FooterSectionManager extends BaseService
{
    /**
     * @param  array<int, mixed>  $configSections
     * @return array<int, FooterSection>
     */
    public function buildSections(array $configSections, FooterBuildContext $ctx): array
    {
        $builtSections = [];
        $seenIds = [];
        $seenTypes = [];
        $drivers = [];

        foreach ($configSections as $section) {
            if (! is_array($section)) {
                $this->logSkipped('invalid', null, 'malformed_section');

                continue;
            }

            $type = is_string($section['type'] ?? null) ? $section['type'] : null;
            $id = is_string($section['id'] ?? null) ? $section['id'] : null;

            if ($type === null || ! $this->registry->has($type)) {
                $this->logSkipped($type ?? 'invalid', $id, 'unknown_type');

                continue;
            }

            if ($id === null || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
                $this->logSkipped($type, $id, 'invalid_id');

                continue;
            }

            if (isset($seenIds[$id])) {
                $this->logSkipped($type, $id, 'duplicate_id');

                continue;
            }

            $driver = $drivers[$type] ??= $this->resolveDriver($type, $id);

            if ($driver === null) {
                continue;
            }

            if (! $driver->supportsMultiple() && isset($seenTypes[$type])) {
                $this->logSkipped($type, $id, 'multiple_not_supported');

                continue;
            }

            $seenIds[$id] = true;
            $seenTypes[$type] = true;

            try {
                $builtSection = $driver->build(new FooterSectionConfig(
                    id: $id,
                    type: $type,
                    enabled: $this->toBool($section['enabled'] ?? true),
                    visibility: is_array($section['visibility'] ?? null) ? $section['visibility'] : [],
                    settings: is_array($section['settings'] ?? null) ? $section['settings'] : [],
                    context: $ctx,
                ));
            } catch (Throwable $exception) {
                $this->logFailed($type, $id, 'driver_exception', [
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            if ($builtSection !== null) {
                $builtSections[] = $builtSection;
            }
        }

        return $builtSections;
    }

    public function __construct(
        private readonly FooterSectionRegistry $registry,
        private readonly Container $container,
    ) {}

    private function resolveDriver(string $type, ?string $sectionId): ?FooterSectionDriver
    {
        $driverClass = $this->registry->driverClassForType($type);

        if (! is_string($driverClass) || $driverClass === '' || ! class_exists($driverClass)) {
            $this->logFailed($type, $sectionId, 'missing_driver_class', [
                'driver_class' => $driverClass,
            ]);

            return null;
        }

        try {
            $driver = $this->container->make($driverClass);
        } catch (Throwable $exception) {
            $this->logFailed($type, $sectionId, 'driver_resolution_failed', [
                'driver_class' => $driverClass,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $driver instanceof FooterSectionDriver) {
            $this->logFailed($type, $sectionId, 'invalid_driver_contract', [
                'driver_class' => $driverClass,
            ]);

            return null;
        }

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logFailed(string $type, ?string $sectionId, string $reason, array $context = []): void
    {
        Log::warning("footer.section.{$type}.failed", array_merge([
            'section_id' => $sectionId,
            'type' => $type,
            'reason' => $reason,
        ], $context));
    }

    private function logSkipped(string $type, ?string $sectionId, string $reason): void
    {
        Log::warning("footer.section.{$type}.skipped", [
            'section_id' => $sectionId,
            'type' => $type,
            'reason' => $reason,
        ]);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
