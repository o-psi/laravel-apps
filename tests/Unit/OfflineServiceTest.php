<?php

namespace EragLaravelPwa\Tests\Unit;

use EragLaravelPwa\Services\OfflineService;
use EragLaravelPwa\Tests\TestCase;

class OfflineServiceTest extends TestCase
{
    protected OfflineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OfflineService();
    }

    /** @test */
    public function it_generates_service_worker_with_configuration(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('const CONFIG =', $sw);
        $this->assertStringContainsString('cacheVersion', $sw);
        $this->assertStringContainsString('strategies', $sw);
    }

    /** @test */
    public function it_generates_head_tag_when_enabled(): void
    {
        config(['offline.enabled' => true]);

        $html = $this->service->headTag();

        $this->assertStringContainsString('manifest.json', $html);
        $this->assertStringContainsString('theme-color', $html);
    }

    /** @test */
    public function it_returns_empty_head_tag_when_disabled(): void
    {
        config(['offline.enabled' => false]);

        $html = $this->service->headTag();

        $this->assertEmpty($html);
    }

    /** @test */
    public function it_generates_status_indicator(): void
    {
        config(['offline.enabled' => true]);

        $html = $this->service->statusIndicator();

        $this->assertStringContainsString('offline-status', $html);
        $this->assertStringContainsString('You are offline', $html);
    }

    /** @test */
    public function it_generates_register_script_when_enabled(): void
    {
        config(['offline.enabled' => true]);

        $html = $this->service->registerScript();

        $this->assertStringContainsString('serviceWorker', $html);
        $this->assertStringContainsString('register', $html);
    }
}
