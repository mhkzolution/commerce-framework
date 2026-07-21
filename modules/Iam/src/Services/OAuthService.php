<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;
use Commerce\Iam\Contracts\OAuth\OAuthServiceInterface;
use Commerce\Iam\Models\OAuthAccount;
use Commerce\Iam\Models\User;
use Commerce\Iam\OAuth\GitHubOAuthProvider;
use Commerce\Iam\OAuth\GoogleOAuthProvider;
use Commerce\Iam\OAuth\OAuthProviderInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class OAuthService extends BaseService implements OAuthServiceInterface
{
    /** @var array<string, OAuthProviderInterface> */
    private array $providers;

    public function __construct(
        GoogleOAuthProvider $google,
        GitHubOAuthProvider $github,
        private readonly IamAuditServiceInterface $audit,
    ) {
        foreach ([$google, $github] as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function enabledProviders(): array
    {
        return array_values(array_map(
            static fn (OAuthProviderInterface $provider): string => $provider->getName(),
            array_filter($this->providers, static fn (OAuthProviderInterface $provider): bool => $provider->isEnabled()),
        ));
    }

    public function getAuthorizationUrl(string $provider, string $redirectUri, ?string $state = null): string
    {
        return $this->resolveProvider($provider)->getAuthorizationUrl($redirectUri, $state);
    }

    public function handleCallback(string $provider, string $code, string $redirectUri): array
    {
        $providerInstance = $this->resolveProvider($provider);
        $profile = $providerInstance->getUserFromCode($code, $redirectUri);

        if ($profile['id'] === '') {
            throw new DomainException('OAuth provider did not return a user id.');
        }

        $account = OAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $profile['id'])
            ->first();

        if ($account !== null) {
            $this->syncTokens($account, $profile);
            $user = $account->user;

            if ($user === null || ! $user->isActive()) {
                throw new DomainException('Linked user account is not active.');
            }

            $this->audit->log('iam.oauth.login', $user, ['provider' => $provider]);

            return ['user' => $user, 'created' => false];
        }

        $user = null;

        if ($profile['email'] !== null) {
            $user = User::query()->where('email', $profile['email'])->first();
        }

        $created = false;

        if ($user === null) {
            $user = User::query()->create([
                'name' => $profile['name'] ?? Str::before((string) $profile['email'], '@'),
                'email' => $profile['email'] ?? "{$provider}_{$profile['id']}@oauth.local",
                'password' => Hash::make(Str::random(32)),
                'status' => 'active',
                'email_verified_at' => $profile['email'] !== null ? now() : null,
            ]);
            $created = true;
        }

        $this->linkAccount($user, $provider, $profile);
        $this->audit->log('iam.oauth.login', $user, ['provider' => $provider, 'created' => $created]);

        return ['user' => $user, 'created' => $created];
    }

    private function resolveProvider(string $provider): OAuthProviderInterface
    {
        if (! isset($this->providers[$provider])) {
            throw new DomainException("OAuth provider [{$provider}] is not supported.");
        }

        $instance = $this->providers[$provider];

        if (! $instance->isEnabled()) {
            throw new DomainException("OAuth provider [{$provider}] is not configured.");
        }

        return $instance;
    }

    /**
     * @param  array{id: string, email: ?string, name: ?string, access_token: ?string, refresh_token: ?string, expires_in: ?int}  $profile
     */
    private function linkAccount(User $user, string $provider, array $profile): void
    {
        OAuthAccount::query()->updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $profile['id'],
            ],
            [
                'user_id' => $user->id,
                'email' => $profile['email'],
                'access_token' => $profile['access_token'],
                'refresh_token' => $profile['refresh_token'],
                'token_expires_at' => isset($profile['expires_in']) ? now()->addSeconds((int) $profile['expires_in']) : null,
            ],
        );
    }

    /**
     * @param  array{id: string, email: ?string, name: ?string, access_token: ?string, refresh_token: ?string, expires_in: ?int}  $profile
     */
    private function syncTokens(OAuthAccount $account, array $profile): void
    {
        $account->forceFill([
            'email' => $profile['email'] ?? $account->email,
            'access_token' => $profile['access_token'],
            'refresh_token' => $profile['refresh_token'] ?? $account->refresh_token,
            'token_expires_at' => isset($profile['expires_in']) ? now()->addSeconds((int) $profile['expires_in']) : $account->token_expires_at,
        ])->save();
    }
}
