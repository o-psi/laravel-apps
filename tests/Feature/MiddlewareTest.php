<?php

namespace Opsi\LaravelOffline\Tests\Feature;

use Opsi\LaravelOffline\Http\Middleware\OfflineMiddleware;
use Opsi\LaravelOffline\Tests\TestCase;
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
        }, 'cache-first,ttl=3600');

        $this->assertEquals('cache-first', $response->headers->get('X-Offline-Strategy'));
        $this->assertEquals('3600', $response->headers->get('X-Offline-TTL'));
    }

    /** @test */
    public function it_parses_middleware_parameters_correctly(): void
    {
        $middleware = new OfflineMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('<html></html>', 200, ['Content-Type' => 'text/html']);
        }, 'network-first,ttl=1800');

        $this->assertEquals('network-first', $response->headers->get('X-Offline-Strategy'));
        $this->assertEquals('1800', $response->headers->get('X-Offline-TTL'));
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
