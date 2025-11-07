<?php

namespace Opsi\LaravelOffline\Tests;

use Opsi\LaravelOffline\OfflineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            OfflineServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up test configuration
        $app['config']->set('offline.enabled', true);
        $app['config']->set('offline.cache_version', 1);
        $app['config']->set('offline.debug', false);
    }
}
