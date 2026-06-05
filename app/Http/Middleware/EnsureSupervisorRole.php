<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisorRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasRole(['supervisor', 'admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Supervisor access required.'], 403);
            }
            
            abort(403, 'Unauthorized. Supervisor access required.');
        }

        return $next($request);
    }
}