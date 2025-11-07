<?php

namespace Opsi\LaravelOffline\Tests\Unit;

use Opsi\LaravelOffline\Services\OfflineService;
use Opsi\LaravelOffline\Tests\TestCase;

class CacheStrategyTest extends TestCase
{
    protected OfflineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OfflineService();
    }

    /** @test */
    public function it_includes_all_cache_strategies_in_service_worker(): void
    {
        $sw = $this->service->generateServiceWorker();

        // Verify all 5 strategies are implemented
        $this->assertStringContainsString('function cacheFirst', $sw);
        $this->assertStringContainsString('function networkFirst', $sw);
        $this->assertStringContainsString('function staleWhileRevalidate', $sw);
        $this->assertStringContainsString('function networkOnly', $sw);
        $this->assertStringContainsString('function cacheOnly', $sw);
    }

    /** @test */
    public function it_includes_route_pattern_matching(): void
    {
        config([
            'offline.strategies' => [
                '/api/*' => 'network-first',
                '/dashboard*' => 'cache-first',
            ],
        ]);

        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('matchesPattern', $sw);
        $this->assertStringContainsString('getStrategy', $sw);
    }

    /** @test */
    public function it_includes_ttl_management(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('isCacheFresh', $sw);
        $this->assertStringContainsString('addCacheMetadata', $sw);
        $this->assertStringContainsString('sw-cached-time', $sw);
    }

    /** @test */
    public function it_includes_cache_size_limits(): void
    {
        config(['offline.cache.max_items' => 100]);

        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('enforceCacheLimits', $sw);
        $this->assertStringContainsString('maxItems', $sw);
    }

    /** @test */
    public function it_respects_cache_version_in_config(): void
    {
        config(['offline.cache_version' => 5]);

        $sw = $this->service->generateServiceWorker();

        // Check if config is properly injected (may be formatted with or without quotes)
        $this->assertTrue(
            str_contains($sw, '"cacheVersion": 5') || str_contains($sw, 'cacheVersion: 5'),
            'Service worker should contain cache version 5'
        );
    }

    /** @test */
    public function it_includes_debug_configuration(): void
    {
        config(['offline.debug' => true]);

        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('"debug": true', $sw);
    }

    /** @test */
    public function it_includes_network_timeout_configuration(): void
    {
        config(['offline.network_timeout' => 5000]);

        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('"networkTimeout": 5000', $sw);
    }

    /** @test */
    public function it_includes_precache_urls(): void
    {
        config([
            'offline.precache' => [
                '/offline.html',
                '/css/app.css',
            ],
        ]);

        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('/offline.html', $sw);
        $this->assertStringContainsString('/css/app.css', $sw);
    }

    /** @test */
    public function it_includes_asset_patterns(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('*.js', $sw);
        $this->assertStringContainsString('*.css', $sw);
        $this->assertStringContainsString('*.woff2', $sw);
    }

    /** @test */
    public function service_worker_handles_install_event(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString("addEventListener('install'", $sw);
        $this->assertStringContainsString('skipWaiting', $sw);
    }

    /** @test */
    public function service_worker_handles_activate_event(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString("addEventListener('activate'", $sw);
        $this->assertStringContainsString('caches.delete', $sw);
    }

    /** @test */
    public function service_worker_handles_fetch_event(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString('handleRequest', $sw);
    }
}
