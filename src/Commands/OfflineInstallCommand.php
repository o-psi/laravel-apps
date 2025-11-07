<?php

namespace EragLaravelPwa\Commands;

use Illuminate\Console\Command;

class OfflineInstallCommand extends Command
{
    protected $signature = 'offline:install';

    protected $description = 'Install Laravel Offline package (publish configs and assets)';

    public function handle(): int
    {
        $this->info('Installing Laravel Offline...');
        $this->newLine();

        // Publish offline config
        $this->info('Publishing offline configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'offline:config',
            '--force' => false,
        ]);

        // Publish PWA config if not exists
        $this->info('Publishing PWA configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'erag:publish-pwa-config',
            '--force' => false,
        ]);

        // Publish resources
        $this->info('Publishing offline page...');
        $this->call('vendor:publish', [
            '--tag' => 'erag:publish-offline',
            '--force' => false,
        ]);

        $this->info('Publishing manifest...');
        $this->call('vendor:publish', [
            '--tag' => 'erag:publish-manifest',
            '--force' => false,
        ]);

        $this->info('Publishing logo...');
        $this->call('vendor:publish', [
            '--tag' => 'erag:publish-logo',
            '--force' => false,
        ]);

        $this->newLine();
        $this->info('✓ Laravel Offline installed successfully!');
        $this->newLine();

        // Show next steps
        $this->line('Next steps:');
        $this->line('  1. Add Blade directives to your layout:');
        $this->line('     <fg=gray>@offlineHead</> in <head>');
        $this->line('     <fg=gray>@offlineScripts</> before </body>');
        $this->line('     <fg=gray>@offlineStatus</> in your navigation (optional)');
        $this->newLine();
        $this->line('  2. Configure cache strategies in <fg=yellow>config/offline.php</>');
        $this->newLine();
        $this->line('  3. Check status: <fg=cyan>php artisan offline:status</>');
        $this->newLine();

        return self::SUCCESS;
    }
}
