<?php

namespace Opsi\LaravelOffline\Tests\Feature;

use Opsi\LaravelOffline\Services\OfflineService;
use Opsi\LaravelOffline\Tests\TestCase;

class BackgroundSyncTest extends TestCase
{
    protected OfflineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OfflineService();
    }

    /** @test */
    public function it_generates_sync_wrapper_with_queue_manager(): void
    {
        config(['offline.enabled' => true]);

        $html = $this->service->syncWrapper('<form action="/test" method="POST"></form>');

        $this->assertStringContainsString('data-offline-sync', $html);
        $this->assertStringContainsString('OfflineQueue', $html);
        $this->assertStringContainsString('enqueue', $html);
    }

    /** @test */
    public function it_handles_offline_form_submissions(): void
    {
        config(['offline.enabled' => true]);

        $html = $this->service->syncWrapper('<form></form>');

        $this->assertStringContainsString('OfflineQueue', $html);
        $this->assertStringContainsString('!navigator.onLine', $html);
        $this->assertStringContainsString('preventDefault', $html);
    }

    /** @test */
    public function it_returns_content_unchanged_when_offline_disabled(): void
    {
        config(['offline.enabled' => false]);

        $content = '<form action="/test" method="POST"></form>';
        $html = $this->service->syncWrapper($content);

        $this->assertEquals($content, $html);
    }

    /** @test */
    public function it_generates_sync_status_widget_when_enabled(): void
    {
        config(['offline.enabled' => true, 'offline.sync.enabled' => true]);

        $html = $this->service->syncStatusWidget();

        $this->assertStringContainsString('sync-status.js', $html);
    }

    /** @test */
    public function it_returns_empty_sync_widget_when_offline_disabled(): void
    {
        config(['offline.enabled' => false]);

        $html = $this->service->syncStatusWidget();

        $this->assertEmpty($html);
    }

    /** @test */
    public function it_returns_empty_sync_widget_when_sync_disabled(): void
    {
        config(['offline.enabled' => true, 'offline.sync.enabled' => false]);

        $html = $this->service->syncStatusWidget();

        $this->assertEmpty($html);
    }

    /** @test */
    public function service_worker_includes_background_sync_configuration(): void
    {
        config([
            'offline.enabled' => true,
            'offline.sync.enabled' => true,
            'offline.sync.retry_interval' => 5000,
            'offline.sync.max_retries' => 3,
        ]);

        $sw = $this->service->generateServiceWorker();

        // Verify service worker includes queue functions
        $this->assertStringContainsString('queueRequest', $sw);
        $this->assertStringContainsString('processBackgroundSync', $sw);
        $this->assertStringContainsString('openQueueDB', $sw);
    }

    /** @test */
    public function service_worker_includes_indexeddb_integration(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('indexedDB', $sw);
        $this->assertStringContainsString('offline-queue', $sw);
        $this->assertStringContainsString('createObjectStore', $sw);
    }

    /** @test */
    public function service_worker_handles_sync_event(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString("addEventListener('sync'", $sw);
        $this->assertStringContainsString('offline-sync', $sw);
    }

    /** @test */
    public function service_worker_handles_queue_request_message(): void
    {
        $sw = $this->service->generateServiceWorker();

        $this->assertStringContainsString('QUEUE_REQUEST', $sw);
        $this->assertStringContainsString("event.data.type === 'QUEUE_REQUEST'", $sw);
    }
}
