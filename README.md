# Laravel Offline

> Production-ready offline-first functionality for Laravel applications

[![License](https://img.shields.io/badge/Licence-MIT-blue)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20to%2012.x-red)](https://laravel.com)

Laravel Offline transforms your Laravel applications into powerful offline-first experiences. Built on top of [laravel-pwa](https://github.com/eramitgupta/laravel-pwa), it adds intelligent caching strategies, background sync, and request queueing.

## Current Status: v0.1.0 - MVP Ready

**Phase 1 Complete** - Enhanced service worker with multiple cache strategies, configuration system, and developer tools.

## Features

### ✅ Available Now (v0.1.0)

- **Multiple Cache Strategies** - cache-first, network-first, stale-while-revalidate, network-only, cache-only
- **Route-Based Caching** - Configure strategies per route pattern with wildcard support
- **Cache Versioning** - Automatic cache invalidation and cleanup
- **Blade Directives** - Simple integration with `@offlineHead`, `@offlineScripts`, `@offlineStatus`
- **Middleware Support** - Per-route cache control via middleware
- **Artisan Commands** - `offline:install`, `offline:status`, `offline:clear`
- **Developer Tools** - Debug logging and cache inspection
- **Offline Status Indicator** - Visual feedback when connection is lost

### 🚧 Coming Soon

- Background sync with IndexedDB queue (Phase 3)
- Form persistence and auto-save (Phase 3)
- Cache inspector UI (Phase 4)
- Advanced conflict resolution (Phase 5)

## Installation

```bash
composer require erag/laravel-pwa
php artisan offline:install
```

## Quick Start

### 1. Add Blade Directives

In your layout file:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    @offlineHead
    <title>My App</title>
</head>
<body>
    <nav>
        @offlineStatus
    </nav>

    {{ $slot }}

    @offlineScripts
</body>
</html>
```

### 2. Configure Cache Strategies

Edit `config/offline.php`:

```php
'strategies' => [
    '/dashboard*' => 'cache-first',
    '/api/*' => 'network-first',
    '/static/*' => 'cache-first',
],

'default_strategy' => 'network-first',
```

### 3. That's It!

Your app now has intelligent offline caching. Routes are cached according to your strategies.

## Usage

### Per-Route Cache Control

```php
Route::get('/dashboard', DashboardController::class)
    ->middleware('offline:cache-first');

Route::get('/profile', ProfileController::class)
    ->middleware('offline:network-first,ttl=3600');
```

### Cache Strategies

| Strategy | Behavior | Best For |
|----------|----------|----------|
| `cache-first` | Check cache first, fall back to network | Static content, rarely changes |
| `network-first` | Try network first, fall back to cache | Dynamic content, prefer fresh |
| `stale-while-revalidate` | Serve cached, fetch fresh in background | News feeds, product lists |
| `network-only` | Always fetch from network | Payment pages, admin panels |
| `cache-only` | Only serve from cache | Offline-only pages |

### Artisan Commands

```bash
# View current configuration
php artisan offline:status

# Clear offline cache (increments version)
php artisan offline:clear

# Install/reinstall package
php artisan offline:install
```

### Blade Directives

```blade
{{-- Add offline head tags --}}
@offlineHead

{{-- Register service worker --}}
@offlineScripts

{{-- Show offline status indicator --}}
@offlineStatus
```

## Configuration

All configuration in `config/offline.php`:

```php
return [
    'enabled' => true,
    'cache_version' => 1,

    // Route patterns with strategies
    'strategies' => [
        '/dashboard*' => 'cache-first',
        '/api/*' => 'network-first',
    ],

    'default_strategy' => 'network-first',

    'cache' => [
        'max_age' => 86400,      // 24 hours
        'max_items' => 100,
    ],

    'network_timeout' => 3000,   // 3 seconds
    'debug' => env('APP_DEBUG', false),
];
```

## Testing

```bash
composer test
```

22 tests covering:
- Service worker generation
- Configuration management
- Blade directives
- Middleware functionality
- Controller responses

## Requirements

- PHP 8.0+
- Laravel 8.x, 9.x, 10.x, 11.x, or 12.x
- HTTPS (required for service workers in production)

## How It Works

1. **Enhanced Service Worker** - Dynamically generated with your configuration
2. **Route Matching** - Wildcard patterns match routes to cache strategies
3. **Smart Caching** - Different strategies for different content types
4. **Auto Versioning** - Increment version to invalidate all caches
5. **Auto Cleanup** - Old cache versions automatically removed

## Roadmap

See [ROADMAP.md](ROADMAP.md) for detailed development plan.

**v0.1.0** (Current) - Enhanced service worker with cache strategies
**v0.2.0** (Next) - Background sync and IndexedDB queue
**v0.3.0** - Form persistence and developer tools
**v1.0.0** - Production-ready with full test coverage

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

Built on [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa) with significant enhancements for offline-first functionality.

---

**Made for the Laravel community**

Star ⭐ the repo if Laravel Offline helps your project!
