<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers\Admin;

use Commerce\Cart\Http\Requests\Admin\UpdateStorefrontNavigationRequest;
use Commerce\Cart\Services\StorefrontNavigationConfig;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StorefrontNavigationController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
        private readonly StorefrontNavigationConfig $navigationConfig,
    ) {}

    public function show(): View
    {
        $settings = $this->settingQueryService->getGroup('storefront');
        $defaults = $this->navigationConfig->defaults();
        $itemsJson = $settings['navigation.items_json'] ?? null;

        if (! is_string($itemsJson) || trim($itemsJson) === '') {
            $itemsJson = json_encode($defaults['items'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return view('cart::admin.navigation.index', [
            'promoEnabled' => filter_var($settings['navigation.promo_enabled'] ?? ($defaults['promo_bar']['enabled'] ?? false), FILTER_VALIDATE_BOOLEAN),
            'promoMessage' => (string) ($settings['navigation.promo_message'] ?? ($defaults['promo_bar']['message'] ?? '')),
            'promoDismissible' => filter_var($settings['navigation.promo_dismissible'] ?? ($defaults['promo_bar']['dismissible'] ?? true), FILTER_VALIDATE_BOOLEAN),
            'itemsJson' => $itemsJson,
        ]);
    }

    public function update(UpdateStorefrontNavigationRequest $request): RedirectResponse
    {
        $itemsJson = trim((string) $request->validated('items_json', ''));

        if ($itemsJson !== '') {
            $decoded = json_decode($itemsJson, true);
            if (! is_array($decoded)) {
                return back()
                    ->withInput()
                    ->withErrors(['items_json' => 'Navigation items must be valid JSON.']);
            }
        }

        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'storefront',
            values: [
                'navigation.promo_enabled' => $request->boolean('promo_enabled'),
                'navigation.promo_message' => $request->validated('promo_message'),
                'navigation.promo_dismissible' => $request->boolean('promo_dismissible'),
                'navigation.items_json' => $itemsJson !== '' ? $itemsJson : null,
            ],
        ));

        return redirect()
            ->route('admin.storefront.navigation.show')
            ->with('status', 'Storefront navigation saved.');
    }
}
