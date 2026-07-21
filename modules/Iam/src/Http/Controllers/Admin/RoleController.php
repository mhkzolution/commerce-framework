<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Contracts\Role\RoleServiceInterface;
use Commerce\Iam\DTO\CreateRoleData;
use Commerce\Iam\DTO\UpdateRoleData;
use Commerce\Iam\Http\Requests\StoreRoleRequest;
use Commerce\Iam\Http\Requests\UpdateRoleRequest;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Services\PermissionQueryService;
use Commerce\Iam\Services\RoleQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

final class RoleController extends Controller
{
    public function __construct(
        private readonly RoleQueryService $queryService,
        private readonly RoleServiceInterface $roleService,
        private readonly PermissionQueryService $permissionQueryService,
    ) {}

    public function index(Request $request): View
    {
        return view('iam::admin.roles.index', [
            'roles' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
            ),
        ]);
    }

    public function create(): View
    {
        return view('iam::admin.roles.create', [
            'permissionsByModule' => $this->permissionQueryService->groupedByModule(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create(new CreateRoleData(
            name: $request->validated('name'),
            code: $request->validated('code'),
            description: $request->validated('description'),
            permissionNames: $request->validated('permissions') ?? [],
        ));

        return redirect()
            ->route('admin.iam.roles.edit', $role)
            ->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('iam::admin.roles.edit', [
            'role' => $role,
            'permissionsByModule' => $this->permissionQueryService->groupedByModule(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role->uuid, new UpdateRoleData(
            name: $request->validated('name'),
            description: $request->validated('description'),
            permissionNames: $request->validated('permissions') ?? [],
        ));

        return redirect()
            ->route('admin.iam.roles.edit', $role)
            ->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        try {
            $this->roleService->delete($role->uuid);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.iam.roles.edit', $role)
                ->withErrors(['delete' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.iam.roles.index')
            ->with('status', 'Role deleted.');
    }
}
