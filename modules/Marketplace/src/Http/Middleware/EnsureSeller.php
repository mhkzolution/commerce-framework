<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Http\Middleware;

use Closure;
use Commerce\Marketplace\Models\Seller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $seller = Seller::query()
            ->where('user_uuid', $user->uuid)
            ->where('status', 'active')
            ->first();

        if ($seller === null) {
            abort(403, 'No active seller account linked to this user.');
        }

        $request->attributes->set('seller', $seller);

        return $next($request);
    }
}
