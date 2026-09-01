<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Iam\Models\Team;
use Commerce\Iam\Models\User;
use Illuminate\Support\Str;

final class TeamService extends BaseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Team
    {
        $slug = Str::slug($data['slug'] ?? $data['name']);

        return Team::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Team $team, array $data): Team
    {
        $team->update([
            'name' => $data['name'] ?? $team->name,
            'slug' => isset($data['slug']) ? Str::slug((string) $data['slug']) : $team->slug,
            'description' => $data['description'] ?? $team->description,
            'status' => $data['status'] ?? $team->status,
        ]);

        return $team->fresh();
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }

    public function addMember(Team $team, User $user, string $role = 'member'): void
    {
        if (! in_array($role, ['member', 'admin'], true)) {
            throw new DomainException('Invalid team role.');
        }

        $team->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);
    }

    public function removeMember(Team $team, User $user): void
    {
        $team->users()->detach($user->id);
    }

    public function findOrFail(string $uuid): Team
    {
        $team = Team::query()->where('uuid', $uuid)->first();

        if ($team === null) {
            throw new EntityNotFoundException("Team [{$uuid}] not found.");
        }

        return $team;
    }
}
