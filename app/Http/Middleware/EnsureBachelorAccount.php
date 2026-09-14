<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to accounts running in Bachelor Split mode. Any other
 * account type (family / individual) gets a 403 — the split features simply
 * don't apply to them.
 */
class EnsureBachelorAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $family = $request->user()?->currentFamily();

        abort_unless($family !== null && $family->isBachelor(), 403);

        return $next($request);
    }
}
