<?php

namespace EragLaravelPwa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfflineMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Add offline caching hints to the response.
     *
     * Usage:
     *   Route::get('/dashboard', DashboardController::class)
     *       ->middleware('offline:cache-first,ttl=3600');
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $strategy = null, ?string $ttl = null): Response
    {
        $response = $next($request);

        // Only apply to successful HTML responses
        if (! $response->isSuccessful() || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        // Parse parameters
        $params = $this->parseParameters($strategy, $ttl);

        // Add cache control headers
        if (isset($params['strategy'])) {
            $response->header('X-Offline-Strategy', $params['strategy']);
        }

        if (isset($params['ttl'])) {
            $response->header('X-Offline-TTL', $params['ttl']);
        }

        return $response;
    }

    /**
     * Parse middleware parameters
     */
    protected function parseParameters(?string $strategy, ?string $ttl): array
    {
        $params = [];

        if ($strategy) {
            $params['strategy'] = $strategy;
        }

        if ($ttl) {
            $params['ttl'] = $ttl;
        }

        return $params;
    }

    /**
     * Check if response is HTML
     */
    protected function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html');
    }
}
