<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has ONE OF the required roles.
     *
     * Usage in routes:
     *   ->middleware('role:student')              — students only
     *   ->middleware('role:tutor,super_admin')    — the teaching workspace
     *   ->middleware('role:super_admin')          — admin-only area
     *
     * Prevents URL hacking — a student cannot reach tutor routes and vice versa.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
