<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Controllers\Admin;

use Commerce\Core\Http\Requests\UpdateSystemModuleStatusRequest;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SystemModuleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ModuleService $modules) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SystemModule::class);

        $search = trim((string) $request->query('search', ''));
        $modules = $this->modules->definitions();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $modules = $modules
                ->filter(static function (SystemModule $module) use ($needle): bool {
                    return str_contains(mb_strtolower($module->name), $needle)
                        || str_contains(mb_strtolower($module->code), $needle)
                        || str_contains(mb_strtolower((string) $module->description), $needle);
                })
                ->values();
        }

        return view('commerce::admin.modules.index', [
            'modules' => $modules,
            'search' => $search,
        ]);
    }

    public function update(UpdateSystemModuleStatusRequest $request, SystemModule $systemModule): RedirectResponse
    {
        $this->authorize('update', $systemModule);

        $this->modules->updateStatus($systemModule, $request->status());

        return redirect()
            ->route('admin.system.modules.index')
            ->with('status', __('commerce::admin.module_updated', ['name' => $systemModule->name]));
    }
}
