<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers\Admin;

use Commerce\Core\Features\FeatureService;
use Commerce\Core\Http\Requests\UpdateSystemFeatureStatusRequest;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SystemFeatureController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly FeatureService $features) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SystemFeature::class);

        $search = trim((string) $request->query('search', ''));
        $features = $this->features->definitions();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $features = $features
                ->filter(static function (SystemFeature $feature) use ($needle): bool {
                    return str_contains(mb_strtolower($feature->name), $needle)
                        || str_contains(mb_strtolower($feature->code), $needle)
                        || str_contains(mb_strtolower((string) $feature->description), $needle)
                        || str_contains(mb_strtolower($feature->module_code), $needle);
                })
                ->values();
        }

        $moduleNames = [];
        $disabledModules = [];

        foreach ($features as $feature) {
            $moduleNames[$feature->module_code] = ModuleService::get($feature->module_code)?->name
                ?? $feature->module_code;
            $disabledModules[$feature->module_code] = ModuleService::isDisabled($feature->module_code);
        }

        return view('commerce::admin.features.index', [
            'features' => $features,
            'search' => $search,
            'moduleNames' => $moduleNames,
            'disabledModules' => $disabledModules,
        ]);
    }

    public function update(UpdateSystemFeatureStatusRequest $request, SystemFeature $systemFeature): RedirectResponse
    {
        $this->authorize('update', $systemFeature);

        $this->features->updateStatus($systemFeature, $request->status());

        return redirect()
            ->route('admin.system.features.index')
            ->with('status', __('commerce::admin.feature_updated', ['name' => $systemFeature->name]));
    }
}
