<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Remove server identifier header if exposed
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Clickjacking protection (prevent embedding in malicious iframes)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Legacy XSS filter protection for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict unnecessary browser features / hardware APIs
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), display-capture=()'
        );

        // Content Security Policy (allows Monaco Editor CDN, fonts, workers, and API connections)
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://accounts.google.com https://apis.google.com",
            "worker-src 'self' blob: data:",
            "child-src 'self' blob: data:",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://fonts.bunny.net https://fonts.googleapis.com",
            "font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://fonts.bunny.net https://fonts.gstatic.com",
            "img-src 'self' data: blob: https://lh3.googleusercontent.com https://*.googleusercontent.com https://*.gravatar.com https://cdn.jsdelivr.net",
            "connect-src 'self' ws: wss: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://accounts.google.com https://api.openai.com https://judge0-ce.p.rapidapi.com",
            "frame-src 'self' https://accounts.google.com https://www.youtube.com https://youtube.com",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        // HTTP Strict Transport Security (HSTS) when on HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
