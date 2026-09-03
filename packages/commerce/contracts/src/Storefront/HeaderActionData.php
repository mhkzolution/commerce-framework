<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class HeaderActionData
{
    /**
     * @param  list<string>  $currencyCodes
     */
    public function __construct(
        public string $searchUrl,
        public string $cartUrl,
        public int $cartCount,
        public bool $authenticated,
        public string $accountUrl,
        public string $loginUrl,
        public string $logoutUrl,
        public array $currencyCodes = [],
        public ?string $currentCurrency = null,
        public ?string $currencyActionUrl = null,
    ) {}
}
