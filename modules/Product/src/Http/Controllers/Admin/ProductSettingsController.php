<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Product\Http\Requests\UpdateProductSettingsRequest;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ProductSettingsController extends Controller
{
    public function __construct(
        private readonly SettingQueryServiceInterface $settingQueryService,
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function show(): View
    {
        $fallbackUuid = $this->settingQueryService->get('product.fallback_image_media_uuid');
        $skuPattern = $this->settingQueryService->get('product.sku_pattern');

        return view('product::admin.settings.index', [
            'fallbackImageMediaUuid' => is_string($fallbackUuid) && $fallbackUuid !== '' ? $fallbackUuid : null,
            'skuPattern' => is_string($skuPattern) && $skuPattern !== '' ? $skuPattern : '{PRODUCT}-{COLOR}-{SIZE}',
        ]);
    }

    public function update(UpdateProductSettingsRequest $request): RedirectResponse
    {
        $this->settingService->updateGroup(new UpdateSettingsGroupData(
            group: 'product',
            values: [
                'fallback_image_media_uuid' => $request->validated('fallback_image_media_uuid'),
                'sku_pattern' => $request->validated('sku_pattern'),
            ],
        ));

        return redirect()
            ->route('admin.products.settings.show')
            ->with('status', 'Product settings saved.');
    }
}
