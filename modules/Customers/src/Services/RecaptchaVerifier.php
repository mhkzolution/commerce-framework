<?php

declare(strict_types=1);

namespace Commerce\Customers\Services;

use Illuminate\Support\Facades\Http;

final class RecaptchaVerifier
{
    public function enabled(): bool
    {
        if (! config('customers.storefront.recaptcha.enabled', false)) {
            return false;
        }

        $siteKey = (string) config('customers.storefront.recaptcha.site_key', '');
        $secretKey = (string) config('customers.storefront.recaptcha.secret_key', '');

        return $siteKey !== '' && $secretKey !== '';
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($token === null || trim($token) === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => (string) config('customers.storefront.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

        if (! $response->successful()) {
            return false;
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        if (! ($payload['success'] ?? false)) {
            return false;
        }

        if (array_key_exists('score', $payload)) {
            return (float) $payload['score'] >= (float) config('customers.storefront.recaptcha.min_score', 0.5);
        }

        return true;
    }
}
