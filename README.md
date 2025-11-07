# Laravel Offline

> True offline-first functionality for Laravel - not just a manifest generator

[![Latest Version](https://img.shields.io/packagist/v/yourvendor/laravel-offline.svg?style=flat-square)](https://packagist.org/packages/yourvendor/laravel-offline)
[![Total Downloads](https://img.shields.io/packagist/dt/yourvendor/laravel-offline.svg?style=flat-square)](https://packagist.org/packages/yourvendor/laravel-offline)
[![License](https://img.shields.io/packagist/l/yourvendor/laravel-offline.svg?style=flat-square)](LICENSE)

Laravel Offline is a package that makes your Laravel applications work seamlessly offline. Built on top of [laravel-pwa](https://github.com/eramitgupta/laravel-pwa), it adds intelligent caching, background sync, and request queueing to create true offline-first web applications.

## ✨ Features

- 🚀 **Advanced Caching Strategies** - Cache-first, network-first, stale-while-revalidate per route
- 📦 **Background Sync** - Queue requests when offline, sync when connection returns
- 💾 **IndexedDB Queue** - Reliable offline data storage with automatic retry
- 🎯 **Route-Based Control** - Configure cache behavior per route via middleware or config
- 📝 **Form Persistence** - Never lose user data - forms auto-save to localStorage
- 🔍 **Developer Tools** - Cache inspector, Artisan commands, debug toolbar
- 🎨 **Blade Directives** - Simple integration with `@offlineHead`, `@offlineScripts`, `@offlineStatus`
- ⚡ **Zero Config** - Works out of the box, powerful when configured

## 🎯 Use Cases

Perfect for:
- SaaS applications needing offline capability
- Field service apps (technicians, sales reps)
- Mobile-first Laravel apps
- Progressive Web Apps (PWA)
- Low-connectivity regions
- Forms that can't afford data loss

## 📦 Installation

```bash
composer require yourvendor/laravel-offline
php artisan offline:install
```

## 🚀 Quick Start

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

### 2. Configure Cache Strategies (Optional)

Edit `config/offline.php`:

```php
'strategies' => [
    '/dashboard*' => 'cache-first',
    '/api/*' => 'network-first',
    '/static/*' => 'cache-first',
],
```

### 3. That's It!

Your app now works offline. Pages are cached, failed requests are queued, and everything syncs when back online.

## 📖 Usage Examples

### Per-Route Cache Control

```php
Route::get('/dashboard', DashboardController::class)
    ->middleware('offline:cache-first,ttl=3600');
```

### Offline-Capable Forms

```blade
@offlineSync
<form action="/api/save" method="POST">
    @csrf
    <input type="text" name="title" required>
    <button type="submit">Save</button>
</form>
@endOfflineSync
```

When offline, the form will be queued and submitted automatically when connection returns.

### Cache Specific Content

```blade
@offlineCache('product-list')
<div class="products">
    @foreach($products as $product)
        <div>{{ $product->name }}</div>
    @endforeach
</div>
@endOfflineCache
```

### Form Auto-Save (Prevent Data Loss)

```blade
<form id="my-form" data-persist="my-unique-form">
    <input type="text" name="title">
    <textarea name="content"></textarea>
    <button type="submit">Save</button>
</form>
```

Form data is automatically saved to localStorage on every keystroke and restored if the user refreshes or crashes.

## 🛠️ Artisan Commands

```bash
# View current configuration
php artisan offline:status

# Clear offline cache (increments version)
php artisan offline:clear

# See which routes have offline middleware
php artisan offline:routes
```

## ⚙️ Configuration

Full configuration options in `config/offline.php`:

```php
return [
    'enabled' => true,

    'cache_version' => 1,

    // Cache strategies per route pattern
    'strategies' => [
        '/dashboard*' => 'cache-first',
        '/api/*' => 'network-first',
    ],

    // Cache settings
    'cache' => [
        'max_age' => 86400,      // 24 hours
        'max_items' => 100,
        'exclude_query_string' => false,
    ],

    // Background sync
    'sync' => [
        'enabled' => true,
        'retry_interval' => 5000,  // 5 seconds
        'max_retries' => 3,
    ],

    'debug' => env('APP_DEBUG', false),
];
```

## 🎨 Cache Strategies

| Strategy | When to Use | Example Routes |
|----------|-------------|----------------|
| `cache-first` | Static content that rarely changes | `/about`, `/blog/*`, static assets |
| `network-first` | Dynamic content, prefer fresh data | `/dashboard`, `/profile`, API endpoints |
| `stale-while-revalidate` | Show cached, fetch fresh in background | `/news`, `/products` |
| `network-only` | Always fetch fresh (no cache) | `/admin/*`, payment pages |
| `cache-only` | Only serve cached (offline-only) | Special offline pages |

## 🔍 Developer Tools

Enable debug mode to see:
- Cache hit/miss statistics
- Pending sync requests
- Cache inspector UI
- Performance metrics

Set in `.env`:

```env
APP_DEBUG=true
```

The cache inspector appears at the bottom-right of your browser when debug is enabled.

## 🧪 Testing

```bash
composer test
```

## 📚 Documentation

- [Quick Start Guide](QUICKSTART.md) - Get up and running in 30 minutes
- [Implementation Guide](IMPLEMENTATION.md) - Detailed technical documentation
- [Project Roadmap](ROADMAP.md) - Features and timeline
- [Contributing](CONTRIBUTING.md) - How to contribute

## 🆚 Comparison with Alternatives

| Feature | Laravel Offline | laravel-pwa | Other PWA Packages |
|---------|----------------|-------------|-------------------|
| PWA Manifest | ✅ | ✅ | ✅ |
| Service Worker | ✅ Enhanced | ✅ Basic | ✅ Basic |
| Multiple Cache Strategies | ✅ | ❌ | ❌ |
| Background Sync | ✅ | ❌ | ❌ |
| Request Queueing | ✅ | ❌ | ❌ |
| Form Persistence | ✅ | ❌ | ❌ |
| Per-Route Control | ✅ | ❌ | ❌ |
| Developer Tools | ✅ | ❌ | ❌ |
| Offline Data Caching | ✅ | ❌ Only offline.html | ❌ |

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

## 🙏 Credits

Built on top of [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa) with significant enhancements for true offline-first functionality.

## 🚀 What's Next?

Check out the [ROADMAP.md](ROADMAP.md) to see what features are coming next!

Current status: **In Development** (aiming for v0.1.0 release)

## 📞 Support

- 🐛 [Issues](https://github.com/yourvendor/laravel-offline/issues)
- 💬 [Discussions](https://github.com/yourvendor/laravel-offline/discussions)
- 📧 Email: your.email@example.com
- 🐦 Twitter: [@yourhandle](https://twitter.com/yourhandle)

---

**Made with ❤️ for the Laravel community**

If this package helps you, consider starring ⭐ the repo!
