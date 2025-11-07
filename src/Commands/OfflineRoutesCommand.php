<?php

namespace Opsi\LaravelOffline\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class OfflineRoutesCommand extends Command
{
    protected $signature = 'offline:routes';

    protected $description = 'List all routes with offline middleware';

    public function handle(): int
    {
        $routes = $this->getOfflineRoutes();

        if (empty($routes)) {
            $this->warn('No routes found with offline middleware');
            $this->newLine();
            $this->line('Add offline middleware to routes like this:');
            $this->line('  <fg=cyan>Route::get(\'/dashboard\', Controller::class)->middleware(\'offline:cache-first\');</>');

            return self::SUCCESS;
        }

        $this->info('Routes with Offline Middleware');
        $this->newLine();

        $tableData = [];
        foreach ($routes as $route) {
            $tableData[] = [
                $route['method'],
                $route['uri'],
                $route['name'] ?? '-',
                $route['strategy'] ?? 'default',
                $route['ttl'] ?? '-',
            ];
        }

        $this->table(
            ['Method', 'URI', 'Name', 'Strategy', 'TTL'],
            $tableData
        );

        $this->newLine();
        $this->info('Total: ' . count($routes) . ' routes');

        return self::SUCCESS;
    }

    protected function getOfflineRoutes(): array
    {
        $routes = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $middleware = $this->getRouteMiddleware($route);

            foreach ($middleware as $m) {
                if (str_starts_with($m, 'offline')) {
                    $routes[] = [
                        'method' => implode('|', $route->methods()),
                        'uri' => $route->uri(),
                        'name' => $route->getName(),
                        'strategy' => $this->extractStrategy($m),
                        'ttl' => $this->extractTtl($m),
                    ];
                    break;
                }
            }
        }

        return $routes;
    }

    protected function getRouteMiddleware(Route $route): array
    {
        return array_merge(
            $route->middleware(),
            $route->gatherMiddleware()
        );
    }

    protected function extractStrategy(string $middleware): ?string
    {
        if ($middleware === 'offline') {
            return null;
        }

        // Extract strategy from offline:cache-first or offline:cache-first,ttl=3600
        preg_match('/offline:([^,]+)/', $middleware, $matches);

        return $matches[1] ?? null;
    }

    protected function extractTtl(string $middleware): ?string
    {
        // Extract TTL from offline:cache-first,ttl=3600
        preg_match('/ttl=(\d+)/', $middleware, $matches);

        return isset($matches[1]) ? $matches[1] . 's' : null;
    }
}
