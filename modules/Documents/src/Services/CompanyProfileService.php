<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\DTO\CompanyProfileData;
use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;

final class CompanyProfileService extends BaseService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public const KEYS = [
        'company.name' => [
            'type' => 'string',
            'label' => 'Company name',
            'group' => 'company',
            'default' => '',
            'is_public' => false,
            'validation' => ['required', 'string', 'max:255'],
        ],
        'company.tax_id' => [
            'type' => 'string',
            'label' => 'Tax ID',
            'group' => 'company',
            'default' => '',
            'is_public' => false,
            'validation' => ['required', 'digits:13'],
        ],
        'company.branch_no' => [
            'type' => 'string',
            'label' => 'Branch number',
            'group' => 'company',
            'default' => CustomerTaxProfile::HEAD_OFFICE_BRANCH,
            'is_public' => false,
            'validation' => ['nullable', 'digits:5'],
        ],
        'company.address' => [
            'type' => 'string',
            'label' => 'Address',
            'group' => 'company',
            'default' => '',
            'is_public' => false,
            'validation' => ['nullable', 'string', 'max:1000'],
        ],
        'company.phone' => [
            'type' => 'string',
            'label' => 'Phone',
            'group' => 'company',
            'default' => '',
            'is_public' => false,
            'validation' => ['nullable', 'string', 'max:50'],
        ],
        'company.email' => [
            'type' => 'string',
            'label' => 'Email',
            'group' => 'company',
            'default' => '',
            'is_public' => false,
            'validation' => ['nullable', 'email', 'max:255'],
        ],
        'company.logo' => [
            'type' => 'string',
            'label' => 'Logo',
            'group' => 'company',
            'default' => null,
            'is_public' => false,
            'validation' => ['nullable', 'uuid'],
        ],
    ];

    public function __construct(
        private readonly SettingQueryServiceInterface $settings,
        private readonly SettingRegistryServiceInterface $registry,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function ensureRegistered(): void
    {
        foreach (self::KEYS as $key => $schema) {
            if ($this->settings->has($key)) {
                continue;
            }

            $this->registry->register($key, array_merge($schema, ['module' => 'documents']));
        }
    }

    public function profile(): CompanyProfileData
    {
        $this->ensureRegistered();

        return CompanyProfileData::fromSettings($this->settings->getGroup('company'));
    }

    public function requireForIssue(): CompanyProfileData
    {
        $profile = $this->profile();

        if (! $profile->isIssuable()) {
            throw new DomainException('Company tax information is incomplete. Set company name and tax ID in settings.');
        }

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        $this->ensureRegistered();

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'company',
            values: $values,
        ));
    }
}
