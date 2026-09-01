<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait UsesApiCart
{
    protected ?string $cartToken = null;

    protected function addToApiCart(string $purchasableUuid, int $quantity = 1): TestResponse
    {
        $response = $this->postJson(route('api.v1.cart.items.store'), [
            'purchasable_uuid' => $purchasableUuid,
            'quantity' => $quantity,
        ])->assertOk();

        $this->rememberCartToken($response);

        return $response;
    }

    protected function rememberCartToken(TestResponse $response): void
    {
        $token = $response->headers->get((string) config('cart.token_header', 'X-Cart-Token'));

        if (! is_string($token) || $token === '') {
            return;
        }

        $this->cartToken = $token;
        $this->withHeader((string) config('cart.token_header', 'X-Cart-Token'), $token);
    }
}
