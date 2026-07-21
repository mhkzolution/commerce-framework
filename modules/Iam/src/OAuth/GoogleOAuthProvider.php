<?php

declare(strict_types=1);

namespace Commerce\Iam\OAuth;

use Illuminate\Support\Facades\Http;

final class GoogleOAuthProvider implements OAuthProviderInterface
{
    public function getName(): string
    {
        return 'google';
    }

    public function isEnabled(): bool
    {
        return config('iam.oauth.google.client_id') !== null
            && config('iam.oauth.google.client_secret') !== null;
    }

    public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string
    {
        $params = http_build_query([
            'client_id' => config('iam.oauth.google.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    public function getUserFromCode(string $code, string $redirectUri): array
    {
        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('iam.oauth.google.client_id'),
            'client_secret' => config('iam.oauth.google.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ])->throw()->json();

        $userResponse = Http::withToken((string) ($tokenResponse['access_token'] ?? ''))
            ->get('https://www.googleapis.com/oauth2/v2/userinfo')
            ->throw()
            ->json();

        return [
            'id' => (string) ($userResponse['id'] ?? ''),
            'email' => $userResponse['email'] ?? null,
            'name' => $userResponse['name'] ?? null,
            'access_token' => $tokenResponse['access_token'] ?? null,
            'refresh_token' => $tokenResponse['refresh_token'] ?? null,
            'expires_in' => isset($tokenResponse['expires_in']) ? (int) $tokenResponse['expires_in'] : null,
        ];
    }
}
