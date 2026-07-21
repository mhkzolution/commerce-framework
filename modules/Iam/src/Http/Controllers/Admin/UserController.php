<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\DTO\UpdateUserData;
use Commerce\Iam\Http\Requests\StoreUserRequest;
use Commerce\Iam\Http\Requests\UpdateUserRequest;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\RoleQueryService;
use Commerce\Iam\Services\UserQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserQueryService $queryService,
        private readonly UserServiceInterface $userService,
        private readonly RoleQueryService $roleQueryService,
    ) {}

    public function index(Request $request): View
    {
        return view('iam::admin.users.index', [
            'users' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
            ),
            'statuses' => config('iam.statuses', []),
        ]);
    }

    public function create(): View
    {
        return view('iam::admin.users.create', [
            'statuses' => config('iam.statuses', []),
            'roles' => $this->roleQueryService->allForSelect(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create(new CreateUserData(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            status: $request->validated('status'),
            firstName: $request->validated('first_name'),
            lastName: $request->validated('last_name'),
            roleCodes: $request->validated('role_codes') ?? [],
        ));

        return redirect()
            ->route('admin.iam.users.edit', $user)
            ->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $user->load(['profile', 'roles']);

        return view('iam::admin.users.edit', [
            'user' => $user,
            'statuses' => config('iam.statuses', []),
            'roles' => $this->roleQueryService->allForSelect(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->update($user->uuid, new UpdateUserData(
                name: $request->validated('name'),
                email: $request->validated('email'),
                status: $request->validated('status'),
                password: $request->validated('password'),
                firstName: $request->validated('first_name'),
                lastName: $request->validated('last_name'),
                roleCodes: $request->validated('role_codes') ?? [],
            ));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.iam.users.edit', $user)
                ->withErrors(['roles' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.iam.users.edit', $user)
            ->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        try {
            $this->userService->delete($user->uuid, $request->user()?->id);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.iam.users.edit', $user)
                ->withErrors(['delete' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.iam.users.index')
            ->with('status', 'User deleted.');
    }
}
