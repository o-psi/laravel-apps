<?php

namespace EragLaravelPwa\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class OfflineStatusCommand extends Command
{
    protected $signature = 'offline:status';

    protected $description = 'Display offline package configuration and status';

    public function handle(): int
    {
        $this->info('Laravel Offline - Configuration Status');
        $this->newLine();

        // Package status
        $enabled = config('offline.enabled', true);
        $this->line('Status: ' . ($enabled ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line('Cache Version: <fg=yellow>' . config('offline.cache_version', 1) . '</>');
        $this->line('Default Strategy: <fg=cyan>' . config('offline.default_strategy', 'network-first') . '</>');
        $this->newLine();

        // Cache settings
        $this->info('Cache Settings:');
        $this->line('  Max Age: ' . config('offline.cache.max_age', 86400) . ' seconds (' . $this->formatDuration(config('offline.cache.max_age', 86400)) . ')');
        $this->line('  Max Items: ' . config('offline.cache.max_items', 100));
        $this->line('  Network Timeout: ' . config('offline.network_timeout', 3000) . 'ms');
        $this->newLine();

        // Route strategies
        $strategies = config('offline.strategies', []);
        if (! empty($strategies)) {
            $this->info('Route Cache Strategies:');
            foreach ($strategies as $pattern => $strategy) {
                $this->line("  <fg=yellow>{$pattern}</> => <fg=cyan>{$strategy}</>");
            }
            $this->newLine();
        }

        // Background sync
        $syncEnabled = config('offline.sync.enabled', true);
        $this->info('Background Sync:');
        $this->line('  Status: ' . ($syncEnabled ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'));
        $this->line('  Retry Interval: ' . config('offline.sync.retry_interval', 5000) . 'ms');
        $this->line('  Max Retries: ' . config('offline.sync.max_retries', 3));
        $this->newLine();

        // Precache
        $precache = config('offline.precache', []);
        if (! empty($precache)) {
            $this->info('Precached URLs:');
            foreach ($precache as $url) {
                $this->line("  <fg=gray>{$url}</>");
            }
            $this->newLine();
        }

        // Debug mode
        $debug = config('offline.debug', false);
        if ($debug) {
            $this->warn('Debug mode is enabled - console logging active');
        }

        $this->newLine();
        $this->info('✓ Offline package is configured and ready');

        return self::SUCCESS;
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);

            return "{$minutes}m";
        }

        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);

            return "{$hours}h";
        }

        $days = floor($seconds / 86400);

        return "{$days}d";
    }
}
