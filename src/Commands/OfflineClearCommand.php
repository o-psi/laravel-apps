<?php

namespace EragLaravelPwa\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OfflineClearCommand extends Command
{
    protected $signature = 'offline:clear
                          {--force : Force cache clear without confirmation}';

    protected $description = 'Clear offline cache by incrementing cache version';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will clear all offline caches for all users. Continue?')) {
            $this->info('Cache clear cancelled');

            return self::SUCCESS;
        }

        $configPath = config_path('offline.php');

        if (! File::exists($configPath)) {
            $this->error('Configuration file not found at: ' . $configPath);
            $this->info('Run: php artisan offline:install');

            return self::FAILURE;
        }

        // Read current config
        $currentVersion = config('offline.cache_version', 1);
        $newVersion = $currentVersion + 1;

        // Update config file
        $configContent = File::get($configPath);
        $updated = preg_replace(
            "/'cache_version' => env\('OFFLINE_CACHE_VERSION', \d+\)/",
            "'cache_version' => env('OFFLINE_CACHE_VERSION', {$newVersion})",
            $configContent
        );

        if ($updated === null || $updated === $configContent) {
            $this->warn('Could not auto-increment version in config file');
            $this->info('Please manually update cache_version in config/offline.php');
            $this->line('Current version: ' . $currentVersion);
            $this->line('New version: ' . $newVersion);

            return self::FAILURE;
        }

        File::put($configPath, $updated);

        $this->info('✓ Cache version incremented: ' . $currentVersion . ' → ' . $newVersion);
        $this->newLine();
        $this->line('All users will receive fresh service worker on next visit.');
        $this->line('Old caches will be automatically cleaned up.');

        return self::SUCCESS;
    }
}
