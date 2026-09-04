<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateCustomerExperienceRequest;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CustomerExperienceController extends Controller
{
    public function __construct(
        private readonly CustomerExperienceConfig $customerExperienceConfig,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $this->customerExperienceConfig->ensureRegistered();

        return view('settings::admin.customer-experience.index', [
            'config' => $this->customerExperienceConfig->resolve(),
            'preview' => $this->customerExperienceConfig->previewCatalog(),
        ]);
    }

    public function update(UpdateCustomerExperienceRequest $request): RedirectResponse
    {
        $this->customerExperienceConfig->ensureRegistered();

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'customer_experience',
            values: [
                'config' => $this->customerExperienceConfig->merge($request->configPayload()),
            ],
        ));

        return redirect()
            ->route('admin.settings.customer-experience.show')
            ->with('status', __('settings::admin.customer_experience_saved'));
    }
}
