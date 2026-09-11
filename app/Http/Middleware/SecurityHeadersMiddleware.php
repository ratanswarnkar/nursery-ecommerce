<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply robust production HTTP security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // MIME Type Sniffing Protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Clickjacking Protection
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Referrer Privacy Protection
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature / Permissions Policy
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy
        // Carefully crafted to permit internal Vite assets, Alpine.js, Tailwind, Google Fonts, and Razorpay standard checkout.
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://checkout.razorpay.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com",
            "frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com",
            "form-action 'self' https://api.razorpay.com",
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        // HTTP Strict Transport Security (HSTS) - Enabled on secure (HTTPS) connections
        if ($request->isSecure() || $request->header('X-Forwarded-Proto') === 'https' || $request->server('HTTPS') === 'on') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
