<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Commerce\Customers\DTO\LineOAuthUser;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class LineOAuthService
{
    public function isConfigured(): bool
    {
        if (! config('customers.storefront.oauth.line.enabled', false)) {
            return false;
        }

        return $this->channelId() !== '' && $this->channelSecret() !== '';
    }

    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->channelId(),
            'redirect_uri' => $this->redirectUri(),
            'state' => $state,
            'scope' => 'profile openid email',
            'bot_prompt' => 'normal',
        ]);

        return 'https://access.line.me/oauth2/v2.1/authorize?'.$query;
    }

    public function exchangeCode(string $code): LineOAuthUser
    {
        $tokenResponse = Http::asForm()
            ->timeout(15)
            ->post('https://api.line.me/oauth2/v2.1/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'client_id' => $this->channelId(),
                'client_secret' => $this->channelSecret(),
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('LINE token exchange failed.');
        }

        /** @var array<string, mixed> $tokenPayload */
        $tokenPayload = $tokenResponse->json();
        $accessToken = is_string($tokenPayload['access_token'] ?? null) ? $tokenPayload['access_token'] : null;
        $idToken = is_string($tokenPayload['id_token'] ?? null) ? $tokenPayload['id_token'] : null;

        if ($accessToken === null) {
            throw new RuntimeException('LINE access token missing.');
        }

        $profileResponse = Http::withToken($accessToken)
            ->timeout(15)
            ->get('https://api.line.me/v2/profile');

        if (! $profileResponse->successful()) {
            throw new RuntimeException('LINE profile request failed.');
        }

        /** @var array<string, mixed> $profile */
        $profile = $profileResponse->json();
        $userId = is_string($profile['userId'] ?? null) ? $profile['userId'] : null;
        $displayName = is_string($profile['displayName'] ?? null) ? $profile['displayName'] : 'LINE User';

        if ($userId === null) {
            throw new RuntimeException('LINE user id missing.');
        }

        $email = $this->emailFromIdToken($idToken);

        return new LineOAuthUser(
            userId: $userId,
            displayName: $displayName,
            email: $email,
            pictureUrl: is_string($profile['pictureUrl'] ?? null) ? $profile['pictureUrl'] : null,
        );
    }

    private function emailFromIdToken(?string $idToken): ?string
    {
        if ($idToken === null) {
            return null;
        }

        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! is_array($payload)) {
            return null;
        }

        $email = $payload['email'] ?? null;

        return is_string($email) && $email !== '' ? $email : null;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }

    private function channelId(): string
    {
        return trim((string) config('customers.storefront.oauth.line.channel_id', ''));
    }

    private function channelSecret(): string
    {
        return trim((string) config('customers.storefront.oauth.line.channel_secret', ''));
    }

    private function redirectUri(): string
    {
        return route('storefront.account.oauth.callback', ['provider' => 'line']);
    }
}
