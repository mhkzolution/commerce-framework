<?php

declare(strict_types=1);

namespace Commerce\Settings\Footer\Drivers;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Throwable;

final class PoweredBySectionDriver implements FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection
    {
        try {
            $planTier = $this->normalizePlanTier($config->context?->planTier);

            if ($planTier === 'enterprise') {
                return null;
            }

            if ($planTier !== 'free' && ! $config->enabled) {
                return null;
            }

            return new FooterSection(
                id: $config->id,
                type: $config->type,
                titleKey: 'settings::footer.section.powered_by',
                meta: [
                    'text' => 'Powered by Commerce Framework',
                    'plan_tier' => $planTier,
                    'required' => $planTier === 'free',
                ],
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function supportsMultiple(): bool
    {
        return false;
    }

    private function normalizePlanTier(?string $planTier): string
    {
        $normalized = strtolower(trim((string) $planTier));

        return $normalized !== '' ? $normalized : 'free';
    }
}
