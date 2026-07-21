<?php

declare(strict_types=1);

namespace Commerce\Contracts\Seo;

interface UrlRedirectServiceInterface
{
    public function createRedirect(string $fromPath, string $toPath, int $type = 301): void;

    public function resolve(string $path): ?string;
}
