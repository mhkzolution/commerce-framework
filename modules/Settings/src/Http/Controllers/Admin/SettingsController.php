<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Controllers\Admin;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Http\Requests\UpdateSettingsRequest;
use Commerce\Settings\Services\SettingQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingQueryService $queryService,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function index(): View
    {
        return view('settings::admin.index', [
            'structure' => $this->queryService->getAdminStructure(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $group): RedirectResponse
    {
        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: $group,
            values: $request->validated('settings', []),
        ));

        return redirect()
            ->route('admin.settings.index')
            ->with('status', "Settings for [{$group}] saved.");
    }

    public function reset(string $group): RedirectResponse
    {
        $this->settingService->resetGroup($group);

        return redirect()
            ->route('admin.settings.index')
            ->with('status', "Settings for [{$group}] reset to defaults.");
    }
}
