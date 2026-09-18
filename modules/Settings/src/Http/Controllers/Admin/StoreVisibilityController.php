<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateStoreVisibilityRequest;
use Commerce\Settings\Services\StoreVisibilityConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StoreVisibilityController extends Controller
{
    public function __construct(
        private readonly StoreVisibilityConfig $storeVisibilityConfig,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $this->storeVisibilityConfig->ensureRegistered();

        return view('settings::admin.store-visibility.index', [
            'mode' => $this->storeVisibilityConfig->mode(),
        ]);
    }

    public function update(UpdateStoreVisibilityRequest $request): RedirectResponse
    {
        $this->storeVisibilityConfig->ensureRegistered();

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'visibility' => $request->validated('visibility'),
            ],
        ));

        return redirect()
            ->route('admin.settings.store-visibility.show')
            ->with('status', __('settings::admin.store_visibility_saved'));
    }
}
