<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->hasRole(['staff', 'admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Staff access required.'], 403);
            }
            
            abort(403, 'Unauthorized. Staff access required.');
        }

        return $next($request);
    }
}