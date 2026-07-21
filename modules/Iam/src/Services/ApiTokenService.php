<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\Models\ApiToken;
use Commerce\Iam\Models\User;
use Illuminate\Support\Str;

final class ApiTokenService extends BaseService implements ApiTokenServiceInterface
{
    public function create(User $user, string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array
    {
        $plainTextToken = 'cf_' . Str::random(48);

        $token = ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }

    public function validate(string $plainTextToken): ?User
    {
        if (! str_starts_with($plainTextToken, 'cf_')) {
            return null;
        }

        $token = ApiToken::query()
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if ($token === null || $token->isExpired()) {
            return null;
        }

        $user = $token->user;

        if ($user === null || ! $user->isActive()) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $user;
    }

    public function listForUser(User $user): array
    {
        return $user->apiTokens()
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    public function revoke(User $user, string $tokenUuid): void
    {
        $deleted = ApiToken::query()
            ->where('user_id', $user->id)
            ->where('uuid', $tokenUuid)
            ->delete();

        if ($deleted === 0) {
            throw new EntityNotFoundException("API token [{$tokenUuid}] not found.");
        }
    }

    public function revokeAll(User $user): void
    {
        ApiToken::query()->where('user_id', $user->id)->delete();
    }
}
