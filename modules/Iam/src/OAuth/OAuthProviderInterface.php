<?php

declare(strict_types=1);

namespace Commerce\Iam\OAuth;

interface OAuthProviderInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string;

    /**
     * @return array{id: string, email: ?string, name: ?string, access_token: ?string, refresh_token: ?string, expires_in: ?int}
     */
    public function getUserFromCode(string $code, string $redirectUri): array;
}
