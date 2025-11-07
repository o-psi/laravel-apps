# Quick Start Guide

Get started building Laravel Offline in 30 minutes.

## Step 1: Choose Your Package Name (5 min)

Pick a good vendor name and package name:
- **Vendor**: Your GitHub username or company name (lowercase, no spaces)
- **Package**: `laravel-offline` (or similar)
- **Namespace**: PascalCase version of vendor

Example:
- Package: `johndoe/laravel-offline`
- Namespace: `JohnDoe\LaravelOffline`

## Step 2: Setup Project Structure (10 min)

```bash
cd /home/psi/laravel-offline

# Copy the base package
cp -r laravel-pwa-base/ package/
cd package/

# Initialize git
git init
git add .
git commit -m "Initial commit - forked from laravel-pwa"

# Create GitHub repo (replace with your username)
gh repo create johndoe/laravel-offline --public --source=. --push
```

## Step 3: Rename Package (10 min)

### 3.1 Update composer.json

```json
{
  "name": "johndoe/laravel-offline",
  "description": "True offline-first functionality for Laravel applications",
  "keywords": ["laravel", "pwa", "offline", "service-worker", "background-sync"],
  "license": "MIT",
  "authors": [
    {
      "name": "Your Name",
      "email": "your.email@example.com"
    }
  ],
  "require": {
    "php": "^8.1",
    "illuminate/support": "^10.0|^11.0"
  },
  "autoload": {
    "psr-4": {
      "JohnDoe\\LaravelOffline\\": "src/"
    },
    "files": [
      "src/helpers.php"
    ]
  },
  "extra": {
    "laravel": {
      "providers": [
        "JohnDoe\\LaravelOffline\\LaravelOfflineServiceProvider"
      ],
      "aliases": {
        "Offline": "JohnDoe\\LaravelOffline\\Facades\\Offline"
      }
    }
  }
}
```

### 3.2 Rename Namespace in All Files

```bash
# Use find and sed to replace namespace
find src/ -type f -name "*.php" -exec sed -i 's/EragLaravelPwa/JohnDoe\\LaravelOffline/g' {} +

# Rename main service provider file
mv src/EragLaravelPwaServiceProvider.php src/LaravelOfflineServiceProvider.php

# Update config references
find src/ -type f -name "*.php" -exec sed -i "s/config('pwa\./config('offline./g" {} +

# Rename config file
mv config/pwa.php config/offline.php
```

### 3.3 Update Service Provider

Edit `src/LaravelOfflineServiceProvider.php`:

```php
<?php

namespace JohnDoe\LaravelOffline;

use JohnDoe\LaravelOffline\Commands\OfflineInstallCommand;
use JohnDoe\LaravelOffline\Commands\OfflineUpdateCommand;
use JohnDoe\LaravelOffline\Services\OfflineService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class LaravelOfflineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OfflineService::class, function ($app) {
            return new OfflineService;
        });

        $this->commands([
            OfflineInstallCommand::class,
            OfflineUpdateCommand::class,
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/../config/offline.php', 'offline'
        );
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/offline.php' => config_path('offline.php'),
        ], 'offline-config');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/manifest.json' => public_path('manifest.json'),
            __DIR__.'/../resources/offline.html' => public_path('offline.html'),
            __DIR__.'/../resources/sw.js' => public_path('sw.js'),
            __DIR__.'/../resources/logo.png' => public_path('logo.png'),
        ], 'offline-assets');

        // Register Blade directives
        Blade::directive('offlineHead', function () {
            return '<?php echo app(\\JohnDoe\\LaravelOffline\\Services\\OfflineService::class)->headTag(); ?>';
        });

        Blade::directive('offlineScripts', function () {
            return '<?php echo app(\\JohnDoe\\LaravelOffline\\Services\\OfflineService::class)->scripts(); ?>';
        });

        Blade::directive('offlineStatus', function () {
            return '<?php echo app(\\JohnDoe\\LaravelOffline\\Services\\OfflineService::class)->statusIndicator(); ?>';
        });

        // Register middleware
        $this->app['router']->aliasMiddleware('offline', \JohnDoe\LaravelOffline\Middleware\OfflineMiddleware::class);

        // Register facade alias
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Offline', \JohnDoe\LaravelOffline\Facades\Offline::class);
        }
    }
}
```

## Step 4: Update Commands (5 min)

Rename command files:

```bash
mv src/Commands/PWACommand.php src/Commands/OfflineUpdateCommand.php
mv src/Commands/PwaPublishCommand.php src/Commands/OfflineInstallCommand.php
```

Update `OfflineInstallCommand.php`:

```php
<?php

namespace JohnDoe\LaravelOffline\Commands;

use Illuminate\Console\Command;

class OfflineInstallCommand extends Command
{
    protected $signature = 'offline:install';
    protected $description = 'Install Laravel Offline package';

    public function handle()
    {
        $this->info('Installing Laravel Offline...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'offline-config',
            '--force' => true,
        ]);

        // Publish assets
        $this->call('vendor:publish', [
            '--tag' => 'offline-assets',
            '--force' => true,
        ]);

        $this->info('✓ Laravel Offline installed successfully!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Add @offlineHead to your <head> tag');
        $this->line('2. Add @offlineScripts before closing </body> tag');
        $this->line('3. Configure strategies in config/offline.php');
        $this->line('4. Run: php artisan offline:status');

        return 0;
    }
}
```

## Step 5: Test Locally (5 min)

Create a test Laravel app:

```bash
cd /home/psi/laravel-offline
laravel new test-app
cd test-app
```

Add local package to composer.json:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../package"
    }
  ],
  "require": {
    "johndoe/laravel-offline": "@dev"
  }
}
```

Install and test:

```bash
composer update
php artisan offline:install
php artisan serve
```

## Step 6: Start Building Phase 1

Now you're ready to start implementing! Begin with:

1. **Enhanced Service Worker** (see IMPLEMENTATION.md Phase 1.2)
2. **Extended Configuration** (see IMPLEMENTATION.md Phase 1.3)
3. **Test in demo app**

## Common Issues

### Namespace errors
- Make sure you replaced ALL occurrences of `EragLaravelPwa`
- Check `composer.json` autoload section
- Run `composer dump-autoload`

### Service provider not loading
- Check `extra.laravel.providers` in composer.json
- For Laravel 11+, check bootstrap/providers.php

### Commands not found
- Make sure commands are registered in service provider
- Run `php artisan list` to see all commands

## Next Steps

1. Read IMPLEMENTATION.md for detailed technical specs
2. Start with Phase 1: Enhanced Service Worker
3. Build incrementally, test as you go
4. Commit often with descriptive messages
5. Write tests alongside features

Good luck! 🚀
