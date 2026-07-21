<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Api;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;
use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\Contracts\Impersonation\ImpersonationServiceInterface;
use Commerce\Iam\Contracts\OAuth\OAuthServiceInterface;
use Commerce\Iam\Contracts\Profile\ProfileServiceInterface;
use Commerce\Iam\Contracts\Security\PasswordResetServiceInterface;
use Commerce\Iam\Contracts\Session\SessionServiceInterface;
use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\Contracts\TwoFactor\TwoFactorServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\DTO\LoginStatus;
use Commerce\Iam\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class AuthApiController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly ApiTokenServiceInterface $tokens,
        private readonly TwoFactorServiceInterface $twoFactor,
        private readonly OAuthServiceInterface $oauth,
        private readonly PasswordResetServiceInterface $passwordReset,
        private readonly ProfileServiceInterface $profiles,
        private readonly SessionServiceInterface $sessions,
        private readonly ImpersonationServiceInterface $impersonation,
        private readonly IamAuditServiceInterface $audit,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $result = $this->authentication->attempt(new LoginCredentialsData(
            email: $validated['email'],
            password: $validated['password'],
            remember: false,
        ));

        if ($result->status === LoginStatus::Failed) {
            return ApiResponse::error('auth.failed', 'Invalid credentials.', status: 401);
        }

        if ($result->status === LoginStatus::TwoFactorRequired) {
            return ApiResponse::success([
                'two_factor_required' => true,
                'challenge_token' => encrypt([
                    'user_id' => $result->user?->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ]),
            ]);
        }

        /** @var User $user */
        $user = $result->user;
        $token = $this->tokens->create($user, $validated['device_name'] ?? 'API Token');
        $this->audit->log('iam.api.login', $user);

        return ApiResponse::success([
            'token' => $token['plainTextToken'],
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $challenge = decrypt($validated['challenge_token']);
        } catch (\Throwable) {
            return ApiResponse::error('auth.invalid_challenge', 'Invalid two-factor challenge.', status: 422);
        }

        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return ApiResponse::error('auth.challenge_expired', 'Two-factor challenge expired.', status: 422);
        }

        $user = User::query()->find($challenge['user_id'] ?? null);

        if (! $user instanceof User || ! $this->twoFactor->verify($user, $validated['code'])) {
            return ApiResponse::error('auth.invalid_code', 'Invalid two-factor code.', status: 422);
        }

        $token = $this->tokens->create($user, $validated['device_name'] ?? 'API Token');
        $this->audit->log('iam.api.login', $user, ['two_factor' => true]);

        return ApiResponse::success([
            'token' => $token['plainTextToken'],
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return ApiResponse::error('auth.unauthenticated', 'Authentication required.', status: 401);
        }

        return ApiResponse::success($this->userPayload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User && $request->bearerToken() !== null) {
            $token = $user->apiTokens()->where('token', hash('sha256', $request->bearerToken()))->first();
            if ($token !== null) {
                $token->delete();
            }
        }

        return ApiResponse::success(['message' => 'Logged out.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $this->passwordReset->sendResetLink($validated['email']);

        return ApiResponse::success(['message' => 'If the account exists, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! $this->passwordReset->reset($validated['email'], $validated['token'], $validated['password'])) {
            return ApiResponse::error('auth.reset_failed', 'Password reset failed.', status: 422);
        }

        return ApiResponse::success(['message' => 'Password has been reset.']);
    }

    public function oauthRedirect(string $provider): JsonResponse
    {
        $redirectUri = route('api.v1.auth.oauth.callback', ['provider' => $provider]);
        $state = Str::random(40);

        return ApiResponse::success([
            'url' => $this->oauth->getAuthorizationUrl($provider, $redirectUri, $state),
            'state' => $state,
        ]);
    }

    public function oauthCallback(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string']]);
        $result = $this->oauth->handleCallback($provider, $validated['code'], route('api.v1.auth.oauth.callback', ['provider' => $provider]));
        $token = $this->tokens->create($result['user'], ucfirst($provider) . ' OAuth');

        return ApiResponse::success([
            'token' => $token['plainTextToken'],
            'token_type' => 'Bearer',
            'created' => $result['created'],
            'user' => $this->userPayload($result['user']),
        ]);
    }

    public function enableTwoFactor(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $setup = $this->twoFactor->enable($user);
        $this->audit->log('iam.two_factor.enable_requested', $user);

        return ApiResponse::success($setup);
    }

    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->confirm($user, $validated['code'])) {
            return ApiResponse::error('auth.invalid_code', 'Invalid two-factor code.', status: 422);
        }

        $codes = $this->twoFactor->generateRecoveryCodes($user->fresh() ?? $user);
        $this->audit->log('iam.two_factor.enabled', $user);

        return ApiResponse::success(['recovery_codes' => $codes]);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->disable($user, $validated['code'])) {
            return ApiResponse::error('auth.invalid_code', 'Invalid two-factor code.', status: 422);
        }

        $this->audit->log('iam.two_factor.disabled', $user);

        return ApiResponse::success(['message' => 'Two-factor authentication disabled.']);
    }

    public function listTokens(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        return ApiResponse::success(array_map(
            static fn ($token): array => [
                'uuid' => $token->uuid,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ],
            $this->tokens->listForUser($user),
        ));
    }

    public function createToken(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
        ]);

        $token = $this->tokens->create($user, $validated['name'], $validated['abilities'] ?? ['*']);
        $this->audit->log('iam.api_token.created', $token['token']);

        return ApiResponse::success([
            'token' => $token['plainTextToken'],
            'token_type' => 'Bearer',
            'meta' => [
                'uuid' => $token['token']->uuid,
                'name' => $token['token']->name,
            ],
        ], status: 201);
    }

    public function revokeToken(Request $request, string $tokenUuid): JsonResponse
    {
        $user = $this->requireUser($request);
        $this->tokens->revoke($user, $tokenUuid);
        $this->audit->log('iam.api_token.revoked', null, ['token_uuid' => $tokenUuid], $user->id);

        return ApiResponse::success(['message' => 'Token revoked.']);
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);

        return ApiResponse::success($this->sessions->listForUser($user));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $profile = $this->profiles->update($user, $validated);

        return ApiResponse::success([
            'user' => $this->userPayload($user->fresh() ?? $user),
            'profile' => $profile->only(['first_name', 'last_name', 'phone', 'bio']),
        ]);
    }

    public function stopImpersonation(Request $request): JsonResponse
    {
        if (! $this->impersonation->isImpersonating()) {
            return ApiResponse::error('iam.not_impersonating', 'Not currently impersonating.', status: 422);
        }

        $this->impersonation->stop();

        return ApiResponse::success(['message' => 'Impersonation stopped.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function requireUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Authentication required.');
        }

        return $user;
    }
}
