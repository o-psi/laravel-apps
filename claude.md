# Laravel Offline

A background runtime package that makes Laravel applications work offline-first, automatically.

## Vision

Make any Laravel application work offline with minimal configuration. The package runs transparently in the background, caching pages and API responses, and syncing data when the connection returns.

## Building on laravel-pwa

This package is built as an enhanced fork of [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa), which provides basic PWA functionality. We're adding comprehensive offline-first capabilities on top of that foundation.

### What laravel-pwa Provides (Base)
- ✅ Basic PWA manifest generation
- ✅ Simple service worker with offline.html fallback
- ✅ Install button UI
- ✅ Blade directives (`@PwaHead`, `@RegisterServiceWorkerScript`)
- ✅ Logo upload functionality
- ✅ Livewire integration support

### What We're Adding (Enhancements)
- 🚀 Advanced caching strategies (cache-first, network-first, stale-while-revalidate)
- 🚀 Background sync for offline requests
- 🚀 IndexedDB for offline data storage and queuing
- 🚀 Route-based caching configuration via config and middleware
- 🚀 Smart cache invalidation and versioning
- 🚀 Offline request queue with conflict resolution
- 🚀 Real-time sync status indicators
- 🚀 Developer debug tools and cache inspector
- 🚀 Per-route cache control
- 🚀 Form persistence across page refreshes

## Core Features

### 1. Automatic Service Worker Integration
- Auto-generate and manage service workers for Laravel apps
- Cache strategies: Network-first, Cache-first, Stale-while-revalidate
- Automatic versioning and cache invalidation

### 2. Transparent Caching Layer
- Intercept HTTP requests and cache responses
- Smart cache strategy selection based on route type
- Blade directive: `@offline` to mark pages/components as offline-capable
- API response caching with TTL support

### 3. Background Sync
- Queue failed requests when offline
- Auto-retry when connection restored
- Handle form submissions, API calls, file uploads
- Conflict resolution strategies

### 4. Developer Experience
- Zero configuration for basic usage
- Simple Blade directives for advanced control
- Debug panel showing cache status, offline requests, sync queue
- Artisan commands for cache management

## Architecture

### Components

1. **ServiceWorkerController** (PHP)
   - Generates service worker JavaScript
   - Handles cache versioning
   - Manages cache strategies configuration

2. **OfflineMiddleware** (PHP)
   - Adds offline headers to responses
   - Marks cacheable routes
   - Injects service worker registration

3. **Service Worker** (JavaScript)
   - Intercepts fetch requests
   - Implements cache strategies
   - Handles background sync
   - Manages IndexedDB for offline data

4. **Blade Directives**
   - `@offline` - Mark content as offline-capable
   - `@offlineSync` - Enable background sync for forms
   - `@offlineStatus` - Show online/offline indicator

5. **Config System**
   - Define cache strategies per route pattern
   - Set cache TTL and size limits
   - Configure sync behavior

### Data Flow

```
User Request → Service Worker → Check Cache
                                ↓
                        Cache Hit? → Return Cached
                                ↓
                        Cache Miss → Network Request
                                     ↓
                                 Cache Response
                                     ↓
                                 Return to User
```

### Offline Request Flow

```
Form Submit (Offline) → IndexedDB Queue
                           ↓
                    Online Event Detected
                           ↓
                    Process Queued Requests
                           ↓
                    Update UI with Results
```

## Technical Stack

- **PHP 8.1+** (for Laravel 10+)
- **Service Workers API** (browser)
- **IndexedDB** (client-side storage)
- **Workbox** (optional - service worker library)
- **Alpine.js integration** (for reactive offline status)

## Implementation Phases

### Phase 1: Fork Setup & Enhanced Service Worker
- [ ] Fork laravel-pwa to our own namespace
- [ ] Upgrade service worker with multiple cache strategies
- [ ] Add strategy router (route patterns → cache strategy)
- [ ] Implement cache versioning with auto-cleanup
- [ ] Add runtime caching for API responses

### Phase 2: Configuration & Middleware
- [ ] Extend config/pwa.php with offline strategies
- [ ] Create `OfflineMiddleware` for per-route cache control
- [ ] Add `@offlineCache` and `@offlineSync` directives
- [ ] Route pattern matching system
- [ ] Cache TTL and size limit controls

### Phase 3: Background Sync & Queue
- [ ] IndexedDB wrapper for offline data storage
- [ ] Request queue management (POST/PUT/DELETE)
- [ ] Background sync API integration
- [ ] Auto-retry with exponential backoff
- [ ] Sync status tracking and events
- [ ] Form auto-save to localStorage

### Phase 4: Developer Experience
- [ ] Cache inspector UI component
- [ ] Artisan commands (`offline:clear`, `offline:status`, `offline:routes`)
- [ ] Debug toolbar integration
- [ ] Performance metrics dashboard
- [ ] Cache hit/miss statistics

### Phase 5: Advanced Features
- [ ] Conflict resolution strategies (server-wins, client-wins, manual)
- [ ] File upload queueing with progress
- [ ] Optimistic UI updates
- [ ] Real-time sync notifications
- [ ] Multi-tab synchronization
- [ ] Push notifications for important syncs

## Usage Examples

### Basic Setup
```php
// config/offline.php
return [
    'enabled' => true,
    'cache_version' => 1,
    'strategies' => [
        '/dashboard*' => 'cache-first',
        '/api/*' => 'network-first',
        '/static/*' => 'cache-first',
    ],
];
```

### Blade Directives
```blade
@offline
<div class="dashboard">
    <!-- This content will be cached and available offline -->
    <h1>Dashboard</h1>
    <p>Last synced: {{ now() }}</p>
</div>
@endoffline

@offlineSync
<form action="/api/save" method="POST">
    @csrf
    <!-- Form will be queued when offline and synced later -->
    <input type="text" name="title">
    <button type="submit">Save</button>
</form>
@endofflineSync

@offlineStatus
<div x-data="{ online: navigator.onLine }">
    <span x-show="online" class="text-green-500">● Online</span>
    <span x-show="!online" class="text-red-500">● Offline</span>
</div>
@endofflineStatus
```

### Route Configuration
```php
Route::get('/dashboard', DashboardController::class)
    ->middleware('offline:cache-first,ttl=3600');

Route::post('/api/data', [ApiController::class, 'store'])
    ->middleware('offline:sync');
```

## Competitive Analysis

### Existing Solutions
- **eramitgupta/laravel-pwa** - Our base! Provides PWA manifest and basic service worker, but only shows offline.html when offline
- **silviolleite/laravel-pwa** - Similar to erag package, basic manifest generation
- **ladumor/laravel-pwa** - Another manifest generator, no real offline functionality
- **Custom Service Workers** - Manual, complex, not Laravel-integrated, requires JS expertise

### Our Advantage Over Alternatives
- **Built on proven foundation** (laravel-pwa with 1000+ stars)
- **True offline-first** - not just an offline page, but actual data caching and sync
- **Zero config** for basic offline support, powerful config for advanced use
- **Laravel-native** Blade directives, middleware, and Artisan commands
- **Automatic background sync** with intelligent conflict resolution
- **Developer-friendly** debug tools, cache inspector, performance metrics
- **Multiple cache strategies** per route (not one-size-fits-all)
- **Form persistence** - never lose user data again
- **Real-time sync status** - users know exactly what's happening

## Market Potential

### Target Users
- SaaS applications needing offline capability
- Field service apps (technicians, sales reps)
- Mobile-first Laravel apps
- Progressive Web Apps (PWA)
- Low-connectivity regions

### Value Proposition
- Reduce bounce rate from network issues
- Improve user experience in poor connectivity
- Enable mobile workforce productivity
- Simple integration (hours, not weeks)

## Success Metrics

- **Adoption**: 1000+ installs in first 6 months
- **GitHub stars**: 500+ stars
- **Community**: Active contributors and issue discussions
- **Use cases**: Featured in Laravel News, blogs, conferences

## Future Enhancements

- Visual cache inspector UI
- Real-time collaboration with offline support
- Automatic asset optimization and caching
- Machine learning for cache prediction
- Multi-device sync
- Electron/Tauri desktop app support

## Getting Started (Development Setup)

### Initial Fork & Rename

1. **Fork the base package**
   ```bash
   cd /home/psi/laravel-offline
   cp -r laravel-pwa-base/ src/
   ```

2. **Rename namespace** from `EragLaravelPwa` to `YourName\LaravelOffline`
   - Update all PHP files: `namespace EragLaravelPwa\...` → `namespace YourName\LaravelOffline\...`
   - Update composer.json autoload section
   - Update service provider references

3. **Update package metadata**
   - composer.json: name, description, authors, keywords
   - README.md: package name, installation instructions
   - config/pwa.php → config/offline.php

4. **Set up development environment**
   ```bash
   composer install
   npm install
   ```

5. **Run tests**
   ```bash
   ./vendor/bin/phpunit
   ```

6. **Create demo Laravel app** for testing
   ```bash
   laravel new demo-app
   cd demo-app
   # Add package via local path in composer.json
   composer require yourname/laravel-offline --dev
   php artisan offline:install
   ```

### Development Workflow

1. Make changes to src/ files
2. Test in demo-app
3. Write tests in tests/
4. Run `composer test` and `npm run lint`
5. Update documentation
6. Commit with descriptive messages

## License

MIT License - Free for personal and commercial use
