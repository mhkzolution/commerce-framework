<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Commerce\Iam\Models\User;
use Commerce\Iam\Services\TwoFactorService;
use Commerce\Iam\Support\TotpGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class IamApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);
    }

    public function test_api_login_returns_token(): void
    {
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'superadmin@example.com',
            'password' => 'password',
            'device_name' => 'PHPUnit',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token', 'user' => ['uuid', 'email']]]);
    }

    public function test_api_login_fails_with_invalid_credentials(): void
    {
        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'superadmin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_authenticated_api_me_endpoint(): void
    {
        $login = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.email', 'superadmin@example.com');
    }

    public function test_api_token_can_be_revoked(): void
    {
        $login = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ]);

        $token = $login->json('data.token');

        $list = $this->withToken($token)->getJson(route('api.v1.auth.tokens.index'));
        $tokenUuid = $list->json('data.0.uuid');

        $this->withToken($token)
            ->deleteJson(route('api.v1.auth.tokens.destroy', $tokenUuid))
            ->assertOk();

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson(route('api.v1.auth.me'))
            ->assertStatus(401);
    }

    public function test_two_factor_login_requires_challenge_when_enabled(): void
    {
        config(['iam.two_factor.enabled' => true]);

        $user = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
        $twoFactor = app(TwoFactorService::class);
        $totp = app(TotpGenerator::class);
        $setup = $twoFactor->enable($user);
        $code = $totp->currentCode($setup['secret']);

        $this->assertTrue($twoFactor->confirm($user->fresh() ?? $user, $code));

        $this->postJson(route('api.v1.auth.login'), [
            'email' => 'superadmin@example.com',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('data.two_factor_required', true);
    }
}
