<?php

namespace EragLaravelPwa\Tests\Unit;

use EragLaravelPwa\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_has_default_offline_configuration(): void
    {
        $this->assertTrue(config('offline.enabled'));
        $this->assertEquals(1, config('offline.cache_version'));
        $this->assertEquals('network-first', config('offline.default_strategy'));
    }

    /** @test */
    public function it_has_cache_configuration(): void
    {
        $this->assertArrayHasKey('max_age', config('offline.cache'));
        $this->assertArrayHasKey('max_items', config('offline.cache'));
        $this->assertEquals(86400, config('offline.cache.max_age'));
    }

    /** @test */
    public function it_has_sync_configuration(): void
    {
        $this->assertTrue(config('offline.sync.enabled'));
        $this->assertEquals(5000, config('offline.sync.retry_interval'));
        $this->assertEquals(3, config('offline.sync.max_retries'));
    }

    /** @test */
    public function it_can_define_cache_strategies(): void
    {
        config([
            'offline.strategies' => [
                '/api/*' => 'network-first',
                '/dashboard*' => 'cache-first',
            ],
        ]);

        $strategies = config('offline.strategies');

        $this->assertArrayHasKey('/api/*', $strategies);
        $this->assertEquals('network-first', $strategies['/api/*']);
    }
}
