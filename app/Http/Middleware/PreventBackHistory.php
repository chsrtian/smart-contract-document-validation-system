<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Enhanced cache control headers with proxy caching prevention
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0, s-maxage=0')
                       ->header('Pragma', 'no-cache')
                       ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
                       ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
                       ->header('Vary', '*')
                       ->header('X-Frame-Options', 'DENY')
                       ->header('X-Content-Type-Options', 'nosniff')
                       ->header('Referrer-Policy', 'no-referrer-when-downgrade')
                       ->header('Surrogate-Control', 'no-store');
    }
}