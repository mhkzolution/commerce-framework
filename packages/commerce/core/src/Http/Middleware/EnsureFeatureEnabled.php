<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Core\Features\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        $enabled = false;

        try {
            $enabled = $this->featureIsEnabled($code);
        } catch (Throwable $exception) {
            Log::warning('Feature middleware failed closed.', [
                'code' => $code,
                'exception' => $exception->getMessage(),
            ]);
        }

        if (! $enabled) {
            abort(404);
        }

        return $next($request);
    }

    protected function featureIsEnabled(string $code): bool
    {
        return FeatureService::enabled($code);
    }
}
