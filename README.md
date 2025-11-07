# Laravel Offline

> Production-ready offline-first functionality for Laravel applications

[![License](https://img.shields.io/badge/Licence-MIT-blue)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20to%2012.x-red)](https://laravel.com)

Laravel Offline transforms your Laravel applications into powerful offline-first experiences. Built on top of [laravel-pwa](https://github.com/eramitgupta/laravel-pwa), it adds intelligent caching strategies, background sync, and request queueing.

## Current Status: v0.2.0 - Enhanced Configuration

**Phase 1 & 2 Complete** - Enhanced service worker, multiple cache strategies, TTL management, form persistence, and comprehensive developer tools.

## Features

### ✅ Available Now (v0.2.0)

- **Multiple Cache Strategies** - cache-first, network-first, stale-while-revalidate, network-only, cache-only
- **Route-Based Caching** - Configure strategies per route pattern with wildcard support
- **Cache TTL Management** - Automatic freshness checking with stale-while-offline support
- **Cache Size Limits** - Automatic FIFO cleanup when cache exceeds max items
- **Form Persistence** - Auto-save forms to localStorage, restore on reload (never lose user data)
- **Blade Directives** - Simple integration with `@offlineHead`, `@offlineScripts`, `@offlineStatus`
- **Middleware Support** - Per-route cache control via middleware
- **Artisan Commands** - `offline:install`, `offline:status`, `offline:clear`, `offline:routes`
- **Developer Tools** - Debug logging with fresh/stale indicators and cache inspection
- **Offline Status Indicator** - Visual feedback when connection is lost

### 🚧 Coming Soon

- Background sync with IndexedDB queue (Phase 3)
- Cache inspector UI (Phase 4)
- Advanced conflict resolution (Phase 5)

## Installation

```bash
composer require opsi/laravel-offline
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

### Form Persistence

Never lose user data - forms auto-save to localStorage:

```html
<script src="/js/form-persistence.js"></script>

<form data-persist="checkout-form">
    <input type="text" name="email">
    <textarea name="notes"></textarea>
    <button type="submit">Save</button>
</form>
```

Data is automatically saved on every keystroke and restored on page load. Cleared on successful submit.

### Artisan Commands

```bash
# View current configuration
php artisan offline:status

# Clear offline cache (increments version)
php artisan offline:clear

# List routes with offline middleware
php artisan offline:routes

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
        'max_items' => 100,       // Max cached items per cache
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
- Service worker generation with TTL management
- Cache size limits and cleanup
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
3. **Smart Caching** - Different strategies for different content types with TTL tracking
4. **Cache Management** - Automatic size limits (FIFO) and freshness checking
5. **Auto Versioning** - Increment version to invalidate all caches
6. **Auto Cleanup** - Old cache versions and stale entries automatically removed
7. **Form Persistence** - Client-side module saves form data every 500ms to localStorage

## Roadmap

See [ROADMAP.md](ROADMAP.md) for detailed development plan.

- ✅ **v0.1.0** - Enhanced service worker with cache strategies
- ✅ **v0.2.0** (Current) - TTL management, cache limits, form persistence
- 🚧 **v0.3.0** (Next) - Background sync with IndexedDB queue
- 📅 **v0.4.0** - Cache inspector UI and debug panel
- 📅 **v1.0.0** - Production-ready with full test coverage

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

MIT License - see [LICENSE](LICENSE) file.

## Credits

Originally forked from [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa) and significantly enhanced with offline-first functionality, multiple cache strategies, background sync, and developer tools.

---

**Made for the Laravel community**

Star ⭐ the repo if Laravel Offline helps your project!
