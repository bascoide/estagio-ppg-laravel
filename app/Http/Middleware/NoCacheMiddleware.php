<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NoCacheMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );

        $response->headers->set('Pragma', 'no-cache');

        $response->headers->set(
            'Expires',
            gmdate('D, d M Y H:i:s') . ' GMT' // serve para indicar quando é que expirou.
        );

        return $response;
    }
}
