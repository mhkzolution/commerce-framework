<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests\Concerns;

use Commerce\Customers\Rules\RecaptchaRule;
use Commerce\Customers\Services\RecaptchaVerifier;

trait ValidatesRecaptcha
{
    /**
     * @return array<string, list<string|RecaptchaRule>>
     */
    protected function recaptchaRules(): array
    {
        if (! app(RecaptchaVerifier::class)->enabled()) {
            return [];
        }

        return [
            'g-recaptcha-response' => ['required', 'string', new RecaptchaRule],
        ];
    }
}
