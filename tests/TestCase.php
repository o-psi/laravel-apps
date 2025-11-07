<?php

namespace EragLaravelPwa\Tests;

use EragLaravelPwa\EragLaravelPwaServiceProvider;
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
            EragLaravelPwaServiceProvider::class,
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
