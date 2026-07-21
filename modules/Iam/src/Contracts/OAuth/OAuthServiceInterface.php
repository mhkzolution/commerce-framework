<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\OAuth;

use Commerce\Iam\Models\User;

interface OAuthServiceInterface
{
    /**
     * @return list<string>
     */
    public function enabledProviders(): array;

    public function getAuthorizationUrl(string $provider, string $redirectUri, ?string $state = null): string;

    /**
     * @return array{user: User, created: bool}
     */
    public function handleCallback(string $provider, string $code, string $redirectUri): array;
}
