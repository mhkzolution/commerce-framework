<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Middleware;

use Closure;
use Commerce\Iam\Models\Team;
use Commerce\Iam\Team\TeamContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTeam
{
    public function __construct(private readonly TeamContext $teamContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->teamContext->isEnabled()) {
            return $next($request);
        }

        $identifier = $request->header((string) config('iam.teams.header', 'X-Team'));

        if (! is_string($identifier) || $identifier === '') {
            return $next($request);
        }

        $team = Team::query()
            ->where('status', 'active')
            ->where(static function ($query) use ($identifier): void {
                $query->where('uuid', $identifier)
                    ->orWhere('slug', $identifier);
            })
            ->first();

        if ($team !== null) {
            $this->teamContext->set($team);
        }

        return $next($request);
    }
}
