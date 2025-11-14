<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to allow token authentication from query parameter
 * Useful for iframe/PDF preview where Authorization header is not sent
 */
class TokenFromQuery
{
    public function handle(Request $request, Closure $next)
    {
        // If token is in query parameter, add it to header
        if ($request->has('token') && !$request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer ' . $request->query('token'));
        }

        return $next($request);
    }
}
