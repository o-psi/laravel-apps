<?php

namespace Opsi\LaravelOffline\Http\Middleware;

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
    public function handle(Request $request, Closure $next, ?string $params = null): Response
    {
        $response = $next($request);

        // Only apply to successful HTML responses
        if (! $response->isSuccessful() || ! $this->isHtmlResponse($response)) {
            return $response;
        }

        // Parse parameters from string like "cache-first,ttl=3600"
        $parsed = $this->parseParameters($params);

        // Add cache control headers
        if (isset($parsed['strategy'])) {
            $response->header('X-Offline-Strategy', $parsed['strategy']);
        }

        if (isset($parsed['ttl'])) {
            $response->header('X-Offline-TTL', $parsed['ttl']);
        }

        return $response;
    }

    /**
     * Parse middleware parameters from string
     *
     * Examples:
     *   "cache-first" => ['strategy' => 'cache-first']
     *   "cache-first,ttl=3600" => ['strategy' => 'cache-first', 'ttl' => '3600']
     *   "network-first,ttl=1800" => ['strategy' => 'network-first', 'ttl' => '1800']
     */
    protected function parseParameters(?string $params): array
    {
        $result = [];

        if (! $params) {
            return $result;
        }

        // Split by comma
        $parts = explode(',', $params);

        foreach ($parts as $part) {
            $part = trim($part);

            // Check if it's a key=value pair
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $result[trim($key)] = trim($value);
            } else {
                // First standalone value is the strategy
                if (! isset($result['strategy'])) {
                    $result['strategy'] = $part;
                }
            }
        }

        return $result;
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
