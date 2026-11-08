<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only a Head of Department gets through (they approve their department's leave).
 */
class EnsureDepartmentHead
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->employee?->rank !== 'head') {
            abort(403);
        }

        return $next($request);
    }
}
