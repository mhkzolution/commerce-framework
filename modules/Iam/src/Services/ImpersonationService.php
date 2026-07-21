<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;
use Commerce\Iam\Contracts\Impersonation\ImpersonationServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class ImpersonationService extends BaseService implements ImpersonationServiceInterface
{
    private const string SESSION_KEY = 'iam.impersonator_id';

    public function __construct(
        private readonly AuthorizationServiceInterface $authorization,
        private readonly IamAuditServiceInterface $audit,
    ) {}

    public function canImpersonate(User $actor, User $target): bool
    {
        if (! config('iam.impersonation.enabled', true)) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        return $this->authorization->can($actor, 'iam.user.update');
    }

    public function start(User $actor, User $target, ?string $reason = null): void
    {
        if (! $this->canImpersonate($actor, $target)) {
            throw new DomainException('You are not allowed to impersonate this user.');
        }

        if (config('iam.impersonation.require_reason', true) && ($reason === null || trim($reason) === '')) {
            throw new DomainException('An impersonation reason is required.');
        }

        Session::put(self::SESSION_KEY, $actor->id);
        Auth::login($target);

        $this->audit->log('iam.impersonation.started', $target, [
            'impersonator_id' => $actor->id,
            'reason' => $reason,
        ], $actor->id);
    }

    public function stop(): void
    {
        $impersonatorId = Session::pull(self::SESSION_KEY);

        if ($impersonatorId === null) {
            return;
        }

        $impersonator = User::query()->find($impersonatorId);
        $target = Auth::user();

        if ($impersonator instanceof User) {
            Auth::login($impersonator);
            $this->audit->log('iam.impersonation.stopped', $target instanceof User ? $target : null, [
                'impersonator_id' => $impersonator->id,
            ], $impersonator->id);
        }
    }

    public function isImpersonating(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public function originalUser(): ?User
    {
        $impersonatorId = Session::get(self::SESSION_KEY);

        if ($impersonatorId === null) {
            return null;
        }

        $user = User::query()->find($impersonatorId);

        return $user instanceof User ? $user : null;
    }
}
