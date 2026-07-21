<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\DTO\UpdateUserData;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Models\UserProfile;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class UserService extends BaseService implements UserServiceInterface
{
    public function __construct(
        private readonly AuthorizationService $authorizationService,
    ) {}

    public function create(CreateUserData $data): User
    {
        $user = User::query()->create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'status' => $data->status,
        ]);

        UserProfile::query()->create([
            'user_id' => $user->id,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
        ]);

        if ($data->roleCodes !== []) {
            $roleIds = Role::query()->whereIn('code', $data->roleCodes)->pluck('id');
            $user->roles()->sync($roleIds);
            $this->authorizationService->clearCacheForUser($user->id);
        }

        return $user->fresh(['profile', 'roles']);
    }

    public function update(string $uuid, UpdateUserData $data): User
    {
        $user = $this->findByUuid($uuid);

        if ($user === null) {
            throw new RuntimeException("User [{$uuid}] not found.");
        }

        $attributes = [
            'name' => $data->name,
            'email' => $data->email,
            'status' => $data->status,
        ];

        if ($data->password !== null && $data->password !== '') {
            $attributes['password'] = Hash::make($data->password);
        }

        $user->update($attributes);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
            ],
        );

        if ($data->roleCodes !== null) {
            $this->assertSuperAdminRetained($user, $data->roleCodes);

            $roleIds = Role::query()->whereIn('code', $data->roleCodes)->pluck('id');
            $user->roles()->sync($roleIds);
            $this->authorizationService->clearCacheForUser($user->id);
        }

        return $user->fresh(['profile', 'roles']);
    }

    public function delete(string $uuid, ?int $actorId = null): void
    {
        $user = $this->findByUuid($uuid);

        if ($user === null) {
            throw new RuntimeException("User [{$uuid}] not found.");
        }

        if ($actorId !== null && $user->id === $actorId) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        if ($this->isLastSuperAdmin($user)) {
            throw new RuntimeException('Cannot delete the last super admin user.');
        }

        $this->authorizationService->clearCacheForUser($user->id);
        $user->roles()->detach();
        $user->delete();
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::query()->where('uuid', $uuid)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    /**
     * @param  list<string>  $roleCodes
     */
    private function assertSuperAdminRetained(User $user, array $roleCodes): void
    {
        $superAdminCode = (string) config('iam.super_admin_role', 'super-admin');

        if (! $this->authorizationService->hasRole($user, $superAdminCode)) {
            return;
        }

        if (in_array($superAdminCode, $roleCodes, true)) {
            return;
        }

        if ($this->countSuperAdmins() <= 1) {
            throw new RuntimeException('Cannot remove the super admin role from the last super admin user.');
        }
    }

    private function isLastSuperAdmin(User $user): bool
    {
        $superAdminCode = (string) config('iam.super_admin_role', 'super-admin');

        if (! $this->authorizationService->hasRole($user, $superAdminCode)) {
            return false;
        }

        return $this->countSuperAdmins() <= 1;
    }

    private function countSuperAdmins(): int
    {
        $superAdminCode = (string) config('iam.super_admin_role', 'super-admin');

        return User::query()
            ->whereHas('roles', static fn ($query) => $query->where('code', $superAdminCode))
            ->count();
    }
}
