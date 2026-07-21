<?php

declare(strict_types=1);

namespace Commerce\Core\Seo;

use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Commerce\Core\Models\UrlRedirect;

final class UrlRedirectService implements UrlRedirectServiceInterface
{
    public function createRedirect(string $fromPath, string $toPath, int $type = 301): void
    {
        $fromPath = $this->normalizePath($fromPath);
        $toPath = $this->normalizePath($toPath);

        UrlRedirect::query()->updateOrCreate(
            ['from_path' => $fromPath, 'tenant_id' => null],
            ['to_path' => $toPath, 'type' => $type],
        );
    }

    public function resolve(string $path): ?string
    {
        $path = $this->normalizePath($path);

        $redirect = UrlRedirect::query()
            ->where('from_path', $path)
            ->first();

        return $redirect?->to_path;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return rtrim($path, '/') ?: '/';
    }
}
