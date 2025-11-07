<?php

namespace Opsi\LaravelOffline\Facades;

use Illuminate\Support\Facades\Facade;

class PWA extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Opsi\LaravelOffline\Services\PWAService::class;
    }
}
