<?php

declare(strict_types=1);

namespace Commerce\Cart\Cart;

use Commerce\Cart\Models\CartToken;

final class CartTokenContext
{
    private ?CartToken $token = null;

    public function set(CartToken $token): void
    {
        $this->token = $token;
    }

    public function token(): ?CartToken
    {
        return $this->token;
    }

    public function hasToken(): bool
    {
        return $this->token !== null;
    }
}
