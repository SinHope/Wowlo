<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has the required role.
     *
     * Usage in routes:  ->middleware('role:tutor')  or  ->middleware('role:student')
     * Prevents URL hacking — a student cannot reach tutor routes and vice versa.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
