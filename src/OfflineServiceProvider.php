<?php

namespace Opsi\LaravelOffline;

use Opsi\LaravelOffline\Commands\OfflineClearCommand;
use Opsi\LaravelOffline\Commands\OfflineInstallCommand;
use Opsi\LaravelOffline\Commands\OfflineRoutesCommand;
use Opsi\LaravelOffline\Commands\OfflineStatusCommand;
use Opsi\LaravelOffline\Commands\PWACommand;
use Opsi\LaravelOffline\Commands\PwaPublishCommand;
use Opsi\LaravelOffline\Http\Controllers\OfflineController;
use Opsi\LaravelOffline\Http\Middleware\OfflineMiddleware;
use Opsi\LaravelOffline\Services\OfflineService;
use Opsi\LaravelOffline\Services\PWAService;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OfflineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
        $this->app->singleton(PWAService::class, function ($app) {
            return new PWAService;
        });

        $this->app->singleton(OfflineService::class, function ($app) {
            return new OfflineService;
        });

        // Merge configurations
        $this->mergeConfigFrom(__DIR__.'/../config/pwa.php', 'pwa');
        $this->mergeConfigFrom(__DIR__.'/../config/offline.php', 'offline');

        // Register commands
        $this->commands([
            PwaPublishCommand::class,
            PWACommand::class,
            OfflineInstallCommand::class,
            OfflineStatusCommand::class,
            OfflineClearCommand::class,
            OfflineRoutesCommand::class,
        ]);

        // Publish configuration files
        $this->publishes([
            __DIR__.'/../config/pwa.php' => config_path('pwa.php'),
        ], 'offline:config');

        $this->publishes([
            __DIR__.'/../config/offline.php' => config_path('offline.php'),
        ], 'offline:config');

        // Publish resources
        $this->publishes([
            __DIR__.'/../resources/manifest.json' => public_path('manifest.json'),
        ], 'offline:resources');

        $this->publishes([
            __DIR__.'/../resources/offline.html' => public_path('offline.html'),
        ], 'offline:resources');

        $this->publishes([
            __DIR__.'/../resources/sw.js' => public_path('sw.js'),
        ], 'offline:resources');

        $this->publishes([
            __DIR__.'/../resources/logo.png' => public_path('logo.png'),
        ], 'offline:resources');

        $this->publishes([
            __DIR__.'/../resources/form-persistence.js' => public_path('js/form-persistence.js'),
        ], 'offline:assets');

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register routes
        $this->registerRoutes();

        // Register middleware
        $this->registerMiddleware();

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register aliases
        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('PWA', \Opsi\LaravelOffline\Facades\PWA::class);
        }
    }

    /**
     * Register package middleware
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('offline', OfflineMiddleware::class);
    }

    /**
     * Register package routes
     */
    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(function () {
                Route::get('/offline-sw.js', [OfflineController::class, 'serviceWorker'])
                    ->name('offline.sw');
            });
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // Original PWA directives
        Blade::directive('PwaHead', function () {
            return '<?php echo app(\\Opsi\\LaravelOffline\\Services\\PWAService::class)->headTag(); ?>';
        });

        Blade::directive('RegisterServiceWorkerScript', function () {
            return '<?php echo app(\\Opsi\\LaravelOffline\\Services\\PWAService::class)->registerServiceWorkerScript(); ?>';
        });

        // New offline directives
        Blade::directive('offlineHead', function () {
            return '<?php echo app(\\Opsi\\LaravelOffline\\Services\\OfflineService::class)->headTag(); ?>';
        });

        Blade::directive('offlineScripts', function () {
            return '<?php echo app(\\Opsi\\LaravelOffline\\Services\\OfflineService::class)->registerScript(); ?>';
        });

        Blade::directive('offlineStatus', function () {
            return '<?php echo app(\\Opsi\\LaravelOffline\\Services\\OfflineService::class)->statusIndicator(); ?>';
        });

        // Cache directive
        Blade::directive('offlineCache', function ($expression) {
            return "<?php ob_start(); ?>";
        });

        Blade::directive('endOfflineCache', function ($expression) {
            return "<?php echo app(\\Opsi\\LaravelOffline\\Services\\OfflineService::class)->cacheWrapper({$expression}, ob_get_clean()); ?>";
        });

        // Sync directive
        Blade::directive('offlineSync', function () {
            return "<?php ob_start(); ?>";
        });

        Blade::directive('endOfflineSync', function () {
            return "<?php echo app(\\Opsi\\LaravelOffline\\Services\\OfflineService::class)->syncWrapper(ob_get_clean()); ?>";
        });
    }
}
