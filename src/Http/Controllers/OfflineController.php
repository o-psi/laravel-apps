<?php

namespace Opsi\LaravelOffline\Http\Controllers;

use Opsi\LaravelOffline\Services\OfflineService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OfflineController extends Controller
{
    protected OfflineService $offlineService;

    public function __construct(OfflineService $offlineService)
    {
        $this->offlineService = $offlineService;
    }

    /**
     * Serve the dynamically generated service worker
     */
    public function serviceWorker(): Response
    {
        $serviceWorker = $this->offlineService->generateServiceWorker();

        return response($serviceWorker)
            ->header('Content-Type', 'application/javascript')
            ->header('Service-Worker-Allowed', '/')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
