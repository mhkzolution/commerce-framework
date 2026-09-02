<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        $disabled = false;

        try {
            $disabled = ModuleService::isDisabled($code);
        } catch (Throwable $exception) {
            Log::warning('Module middleware failed open.', [
                'code' => $code,
                'exception' => $exception->getMessage(),
            ]);
        }

        if ($disabled) {
            abort(404);
        }

        return $next($request);
    }
}
