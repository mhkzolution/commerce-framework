<?php

declare(strict_types=1);

namespace Commerce\Customers\DTO;

final readonly class LineOAuthUser
{
    public function __construct(
        public string $userId,
        public string $displayName,
        public ?string $email = null,
        public ?string $pictureUrl = null,
    ) {}
}
