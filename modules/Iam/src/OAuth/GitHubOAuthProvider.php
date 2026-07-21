<?php

declare(strict_types=1);

namespace Commerce\Iam\OAuth;

use Illuminate\Support\Facades\Http;

final class GitHubOAuthProvider implements OAuthProviderInterface
{
    public function getName(): string
    {
        return 'github';
    }

    public function isEnabled(): bool
    {
        return config('iam.oauth.github.client_id') !== null
            && config('iam.oauth.github.client_secret') !== null;
    }

    public function getAuthorizationUrl(string $redirectUri, ?string $state = null): string
    {
        $params = http_build_query([
            'client_id' => config('iam.oauth.github.client_id'),
            'redirect_uri' => $redirectUri,
            'scope' => 'user:email',
            'state' => $state,
        ]);

        return 'https://github.com/login/oauth/authorize?' . $params;
    }

    public function getUserFromCode(string $code, string $redirectUri): array
    {
        $tokenResponse = Http::asForm()
            ->accept('application/json')
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('iam.oauth.github.client_id'),
                'client_secret' => config('iam.oauth.github.client_secret'),
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ])
            ->throw()
            ->json();

        $accessToken = (string) ($tokenResponse['access_token'] ?? '');

        $userResponse = Http::withToken($accessToken)
            ->accept('application/vnd.github+json')
            ->get('https://api.github.com/user')
            ->throw()
            ->json();

        $email = $userResponse['email'] ?? null;

        if ($email === null) {
            $emails = Http::withToken($accessToken)
                ->accept('application/vnd.github+json')
                ->get('https://api.github.com/user/emails')
                ->throw()
                ->json();

            foreach ($emails as $entry) {
                if (($entry['primary'] ?? false) === true) {
                    $email = $entry['email'] ?? null;
                    break;
                }
            }
        }

        return [
            'id' => (string) ($userResponse['id'] ?? ''),
            'email' => $email,
            'name' => $userResponse['name'] ?? $userResponse['login'] ?? null,
            'access_token' => $accessToken,
            'refresh_token' => null,
            'expires_in' => null,
        ];
    }
}
