<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class LineOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'customers.storefront.oauth.line.enabled' => true,
            'customers.storefront.oauth.line.channel_id' => 'line-channel-id',
            'customers.storefront.oauth.line.channel_secret' => 'line-channel-secret',
            'app.url' => 'http://commerce.test',
        ]);
    }

    public function test_line_redirect_starts_oauth_flow(): void
    {
        $response = $this->get(route('storefront.account.oauth.redirect', ['provider' => 'line']));

        $response->assertRedirect();
        $this->assertStringContainsString('access.line.me/oauth2/v2.1/authorize', (string) $response->headers->get('Location'));
        $this->assertNotNull(session('customer.oauth.state'));
    }

    public function test_line_callback_creates_customer_and_logs_in(): void
    {
        $state = 'expected-state-token';
        session(['customer.oauth.state' => $state]);

        Http::fake([
            'https://api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'line-access-token',
                'id_token' => $this->fakeIdToken('line-user-1', 'line.user@example.com'),
            ]),
            'https://api.line.me/v2/profile' => Http::response([
                'userId' => 'line-user-1',
                'displayName' => 'LINE Shopper',
                'pictureUrl' => 'https://profile.line-scdn.net/example.jpg',
            ]),
        ]);

        $this->get(route('storefront.account.oauth.callback', [
            'provider' => 'line',
            'code' => 'auth-code',
            'state' => $state,
        ]))->assertRedirect(route('storefront.account'));

        $customer = Customer::query()->where('line_user_id', 'line-user-1')->first();
        $this->assertNotNull($customer);
        $this->assertSame('line.user@example.com', $customer->email);
        $this->assertSame('LINE Shopper', $customer->name);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_line_callback_logs_in_existing_customer(): void
    {
        $customer = Customer::query()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
            'line_user_id' => 'line-user-2',
            'status' => 'active',
        ]);

        $state = 'expected-state-token-2';
        session(['customer.oauth.state' => $state]);

        Http::fake([
            'https://api.line.me/oauth2/v2.1/token' => Http::response([
                'access_token' => 'line-access-token',
            ]),
            'https://api.line.me/v2/profile' => Http::response([
                'userId' => 'line-user-2',
                'displayName' => 'Existing User',
            ]),
        ]);

        $this->get(route('storefront.account.oauth.callback', [
            'provider' => 'line',
            'code' => 'auth-code',
            'state' => $state,
        ]))->assertRedirect(route('storefront.account'));

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    private function fakeIdToken(string $sub, string $email): string
    {
        $header = rtrim(strtr(base64_encode('{"alg":"none"}'), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'sub' => $sub,
            'email' => $email,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $header.'.'.$payload.'.signature';
    }
}
