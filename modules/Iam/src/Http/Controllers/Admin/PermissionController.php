<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Services\PermissionQueryService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionQueryService $permissionQueryService,
    ) {}

    public function index(Request $request): View
    {
        $permissionsByModule = $this->permissionQueryService->groupedByModule();

        if ($request->filled('module')) {
            $module = $request->string('module')->toString();
            $permissionsByModule = $permissionsByModule->only([$module]);
        }

        return view('iam::admin.permissions.index', [
            'permissionsByModule' => $permissionsByModule,
            'modules' => $this->permissionQueryService->groupedByModule()->keys()->all(),
        ]);
    }
}
