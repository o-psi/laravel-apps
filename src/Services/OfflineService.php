<?php

namespace Opsi\LaravelOffline\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class OfflineService
{
    /**
     * Generate service worker JavaScript with injected configuration
     */
    public function generateServiceWorker(): string
    {
        $config = $this->getServiceWorkerConfig();
        $template = $this->getServiceWorkerTemplate();

        // Inject configuration into service worker
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        $serviceWorker = preg_replace(
            '/const CONFIG = \{[^}]*\};/s',
            'const CONFIG = ' . $configJson . ';',
            $template
        );

        return $serviceWorker;
    }

    /**
     * Get configuration for service worker
     */
    protected function getServiceWorkerConfig(): array
    {
        return [
            'cacheVersion' => config('offline.cache_version', 1),
            'debug' => config('offline.debug', false),
            'networkTimeout' => config('offline.network_timeout', 3000),
            'cacheNames' => config('offline.cache_names', [
                'pages' => 'offline-pages',
                'assets' => 'offline-assets',
                'api' => 'offline-api',
                'runtime' => 'offline-runtime',
            ]),
            'strategies' => config('offline.strategies', []),
            'defaultStrategy' => config('offline.default_strategy', 'network-first'),
            'precache' => config('offline.precache', ['/offline.html']),
            'assetPatterns' => config('offline.cache.assets', [
                '*.js', '*.css', '*.woff', '*.woff2', '*.ttf', '*.eot',
                '*.svg', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp',
            ]),
            'maxAge' => config('offline.cache.max_age', 86400),
            'maxItems' => config('offline.cache.max_items', 100),
        ];
    }

    /**
     * Get service worker template
     */
    protected function getServiceWorkerTemplate(): string
    {
        $templatePath = __DIR__ . '/../../resources/offline-sw.js';

        if (! File::exists($templatePath)) {
            throw new \RuntimeException('Service worker template not found at: ' . $templatePath);
        }

        return File::get($templatePath);
    }

    /**
     * Generate offline status indicator HTML
     */
    public function statusIndicator(): string
    {
        if (! config('offline.enabled', true)) {
            return '';
        }

        return <<<'HTML'
        <div id="offline-status" class="offline-status" style="display: none;">
            <span class="offline-status-icon">⚠️</span>
            <span class="offline-status-text">You are offline</span>
        </div>
        <style>
            .offline-status {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #f59e0b;
                color: white;
                padding: 0.5rem 1rem;
                text-align: center;
                z-index: 9999;
                font-size: 0.875rem;
                font-weight: 500;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .offline-status-icon {
                margin-right: 0.5rem;
            }
        </style>
        <script>
            (function() {
                const statusEl = document.getElementById('offline-status');

                function updateOnlineStatus() {
                    if (!navigator.onLine) {
                        statusEl.style.display = 'block';
                    } else {
                        statusEl.style.display = 'none';
                    }
                }

                window.addEventListener('online', updateOnlineStatus);
                window.addEventListener('offline', updateOnlineStatus);
                updateOnlineStatus();
            })();
        </script>
        HTML;
    }

    /**
     * Generate offline head tags
     */
    public function headTag(): string
    {
        if (! config('offline.enabled', true)) {
            return '';
        }

        $manifest = asset('/manifest.json');
        $themeColor = config('pwa.manifest.theme_color', '#6777ef');
        $icon = asset(config('pwa.manifest.icons.src', 'logo.png'));

        return <<<HTML
        <!-- Laravel Offline PWA -->
        <meta name="theme-color" content="{$themeColor}"/>
        <link rel="apple-touch-icon" href="{$icon}">
        <link rel="manifest" href="{$manifest}">
        <!-- Laravel Offline PWA end -->
        HTML;
    }

    /**
     * Generate service worker registration script
     */
    public function registerScript(): string
    {
        if (! config('offline.enabled', true)) {
            return '';
        }

        $swPath = route('offline.sw');
        $isDebug = config('offline.debug', false);
        $consoleLog = $isDebug ? 'console.log' : '//';
        $isLivewire = config('pwa.livewire-app', false) ? 'data-navigate-once' : '';

        return <<<HTML
        <!-- Laravel Offline Service Worker -->
        <script {$isLivewire}>
            "use strict";
            if ("serviceWorker" in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register("{$swPath}")
                        .then(function(registration) {
                            {$consoleLog}("[Offline] Service worker registered:", registration);

                            // Check for updates
                            registration.addEventListener('updatefound', function() {
                                const newWorker = registration.installing;
                                {$consoleLog}("[Offline] Service worker update found");

                                newWorker.addEventListener('statechange', function() {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        {$consoleLog}("[Offline] New service worker available");
                                        // Optionally notify user about update
                                    }
                                });
                            });
                        })
                        .catch(function(error) {
                            {$consoleLog}("[Offline] Service worker registration failed:", error);
                        });
                });
            } else {
                {$consoleLog}("[Offline] Service workers are not supported.");
            }
        </script>
        <!-- Laravel Offline Service Worker end -->
        HTML;
    }

    /**
     * Generate offline cache wrapper for content
     */
    public function cacheWrapper(string $key, string $content): string
    {
        if (! config('offline.enabled', true)) {
            return $content;
        }

        return <<<HTML
        <div data-offline-cache="{$key}">
            {$content}
        </div>
        HTML;
    }

    /**
     * Generate offline sync wrapper for forms
     */
    public function syncWrapper(string $content): string
    {
        if (! config('offline.enabled', true)) {
            return $content;
        }

        return <<<HTML
        <div data-offline-sync>
            {$content}
        </div>
        <script>
            (function() {
                const form = document.querySelector('[data-offline-sync] form');
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    if (!navigator.onLine) {
                        e.preventDefault();

                        // Queue the request for background sync
                        const formData = new FormData(form);
                        const data = {
                            url: form.action,
                            method: form.method,
                            data: Object.fromEntries(formData)
                        };

                        // Store in IndexedDB (implementation in Phase 3)
                        console.log('[Offline] Form queued for sync:', data);

                        // Show user feedback
                        alert('You are offline. This form will be submitted when you reconnect.');
                    }
                });
            })();
        </script>
        HTML;
    }
}
