<?php

namespace EragLaravelPwa\Tests\Feature;

use EragLaravelPwa\Tests\TestCase;

class ServiceWorkerControllerTest extends TestCase
{
    /** @test */
    public function it_serves_service_worker_at_correct_route(): void
    {
        $response = $this->get('/offline-sw.js');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/javascript');
    }

    /** @test */
    public function it_includes_cache_control_headers(): void
    {
        $response = $this->get('/offline-sw.js');

        $response->assertHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /** @test */
    public function it_includes_service_worker_scope_header(): void
    {
        $response = $this->get('/offline-sw.js');

        $response->assertHeader('Service-Worker-Allowed', '/');
    }

    /** @test */
    public function service_worker_contains_configuration(): void
    {
        $response = $this->get('/offline-sw.js');

        $content = $response->getContent();
        $this->assertStringContainsString('const CONFIG =', $content);
        $this->assertStringContainsString('cacheVersion', $content);
        $this->assertStringContainsString('strategies', $content);
    }
}
