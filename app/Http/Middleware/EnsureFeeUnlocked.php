<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the student fee section until the parent has entered the shared
 * FEE_VIEW_PASSWORD for this session.
 */
class EnsureFeeUnlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('fee_unlocked')) {
            return redirect()->route('student.fees.unlock');
        }

        return $next($request);
    }
}
