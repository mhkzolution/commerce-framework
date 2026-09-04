<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

final readonly class WebsiteContactData
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
    ) {}
}
