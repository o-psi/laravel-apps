<?php

namespace EragLaravelPwa\Tests\Feature;

use EragLaravelPwa\Http\Middleware\OfflineMiddleware;
use EragLaravelPwa\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MiddlewareTest extends TestCase
{
    /** @test */
    public function it_adds_cache_strategy_header(): void
    {
        $middleware = new OfflineMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('<html></html>', 200, ['Content-Type' => 'text/html']);
        }, 'cache-first');

        $this->assertEquals('cache-first', $response->headers->get('X-Offline-Strategy'));
    }

    /** @test */
    public function it_adds_ttl_header_when_specified(): void
    {
        $middleware = new OfflineMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('<html></html>', 200, ['Content-Type' => 'text/html']);
        }, 'cache-first', '3600');

        $this->assertEquals('3600', $response->headers->get('X-Offline-TTL'));
    }

    /** @test */
    public function it_only_applies_to_html_responses(): void
    {
        $middleware = new OfflineMiddleware();
        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('{"data": "test"}', 200, ['Content-Type' => 'application/json']);
        }, 'cache-first');

        $this->assertNull($response->headers->get('X-Offline-Strategy'));
    }

    /** @test */
    public function it_only_applies_to_successful_responses(): void
    {
        $middleware = new OfflineMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('<html></html>', 404, ['Content-Type' => 'text/html']);
        }, 'cache-first');

        $this->assertNull($response->headers->get('X-Offline-Strategy'));
    }
}
