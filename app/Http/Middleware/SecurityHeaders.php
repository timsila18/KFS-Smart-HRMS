<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = config('security.headers');

        $response->headers->set('X-Frame-Options', $headers['x_frame_options']);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', $headers['referrer_policy']);
        $response->headers->set('Permissions-Policy', $headers['permissions_policy']);

        if (! app()->environment('local', 'testing')) {
            $response->headers->set('Content-Security-Policy', $headers['content_security_policy']);
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
