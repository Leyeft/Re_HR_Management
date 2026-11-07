<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to Admin and HR accounts. Used for managing employees
 * and departments in this closed HR system.
 */
class EnsureAdminOrHr
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['admin', 'hr'], true)) {
            abort(403);
        }

        return $next($request);
    }
}
