# Laravel Offline - True Offline-First for Laravel

> Enhanced fork of [laravel-pwa](https://github.com/eramitgupta/laravel-pwa) with advanced offline capabilities

<div align="center">

[![License](https://img.shields.io/badge/Licence-MIT-blue)](LICENSE)
[![Status](https://img.shields.io/badge/Status-In%20Development-orange)](https://github.com/o-psi/laravel-apps)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20to%2012.x-red)](https://laravel.com)

</div>

## 🎯 Vision

Transform Laravel applications into true offline-first experiences. Not just a PWA manifest generator - this package provides intelligent caching, background sync, and request queueing so your Laravel apps work seamlessly when users lose connectivity.

## 📊 Current Status: **In Development**

This is an active fork of [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa) being enhanced with production-ready offline functionality.

**Base Package (Working Now)**: ✅ All laravel-pwa features

**Enhancements (In Progress)**: 🚧 See roadmap below

---

## ✨ Features

### ✅ Available Now (Base laravel-pwa)

- ⚙️ Auto-generate PWA manifest and service worker files
- 🧩 Configurable "Add to Home Screen" install prompt
- 📱 Fully responsive - works on mobile and desktop
- 🛠️ Customizable via `config/pwa.php`
- 🧑‍💻 Blade directives (`@PwaHead`, `@RegisterServiceWorkerScript`)
- 🔐 HTTPS ready
- 🌐 Compatible with Blade, Livewire, Vue 3, and React
- 🔄 Supports Laravel 8.x to 12.x

### 🚧 Coming Soon (Offline Enhancements)

- 🚀 **Advanced Caching Strategies** - cache-first, network-first, stale-while-revalidate per route
- 📦 **Background Sync** - queue requests when offline, auto-sync when online
- 💾 **IndexedDB Storage** - reliable offline data storage with retry logic
- 🎯 **Per-Route Control** - configure cache behavior via middleware or config
- 📝 **Form Persistence** - auto-save forms to localStorage, never lose data
- 🔍 **Developer Tools** - cache inspector, Artisan commands, debug panel
- ⚡ **Zero Config** - works out of the box, powerful when configured
- 🎨 **New Directives** - `@offlineCache`, `@offlineSync`, `@offlineStatus`

---

## 📦 Installation (Current Base Package)

```bash
composer require erag/laravel-pwa
```

### For Laravel 11.x, 12.x

Register in `/bootstrap/providers.php`:

```php
use EragLaravelPwa\EragLaravelPwaServiceProvider;

return [
    // ...
    EragLaravelPwaServiceProvider::class,
];
```

### For Laravel 8.x, 9.x, 10.x

Register in `config/app.php`:

```php
'providers' => [
    // ...
    EragLaravelPwa\EragLaravelPwaServiceProvider::class,
],
```

### Publish Assets

```bash
php artisan erag:install-pwa
```

---

## 🚀 Quick Start

### 1. Add Blade Directives

In your main layout file:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    @PwaHead
    <title>My App</title>
</head>
<body>
    <!-- Your content -->

    @RegisterServiceWorkerScript
</body>
</html>
```

### 2. Configure (Optional)

Edit `config/pwa.php`:

```php
return [
    'install-button' => true,

    'manifest' => [
        'name' => 'My Laravel App',
        'short_name' => 'MyApp',
        'background_color' => '#ffffff',
        'theme_color' => '#6777ef',
        'display' => 'standalone',
        'icons' => [
            [
                'src' => 'logo.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ],

    'debug' => env('APP_DEBUG', false),
    'livewire-app' => false,
];
```

### 3. Update Manifest

After config changes:

```bash
php artisan erag:update-manifest
```

---

## 🎯 Planned Usage (Offline Enhancements)

These features are planned and documented in [OFFLINE_PLAN.md](OFFLINE_PLAN.md).

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
@endofflineSync
```

### Form Auto-Save

```blade
<form id="my-form" data-persist="my-unique-form">
    <input type="text" name="title">
    <textarea name="content"></textarea>
    <button type="submit">Save</button>
</form>
```

### Cache Strategies Configuration

```php
// config/offline.php (future)
'strategies' => [
    '/dashboard*' => 'cache-first',
    '/api/*' => 'network-first',
    '/static/*' => 'cache-first',
],
```

---

## 🗺️ Roadmap

### Phase 1: Enhanced Service Worker (Weeks 1-2)
- [ ] Multiple cache strategies (network-first, cache-first, stale-while-revalidate)
- [ ] Route pattern matching
- [ ] Cache versioning and auto-cleanup
- [ ] Runtime API caching

### Phase 2: Configuration & Middleware (Weeks 3-4)
- [ ] Offline middleware for per-route control
- [ ] Extended config with cache strategies
- [ ] New Blade directives
- [ ] Cache TTL and size limits

### Phase 3: Background Sync (Weeks 5-6)
- [ ] IndexedDB queue manager
- [ ] Request queueing (POST/PUT/DELETE)
- [ ] Auto-retry with backoff
- [ ] Form auto-save to localStorage

### Phase 4: Developer Tools (Weeks 7-8)
- [ ] Cache inspector UI
- [ ] Artisan commands (`offline:clear`, `offline:status`)
- [ ] Debug toolbar integration
- [ ] Performance metrics

### Phase 5: Production Ready (Weeks 9-12)
- [ ] Conflict resolution strategies
- [ ] File upload queueing
- [ ] Multi-tab synchronization
- [ ] Comprehensive testing
- [ ] v1.0.0 release

**See [ROADMAP.md](ROADMAP.md) for detailed timeline and milestones.**

---

## 📚 Documentation

- **[OFFLINE_PLAN.md](OFFLINE_PLAN.md)** - Complete vision and features
- **[IMPLEMENTATION.md](IMPLEMENTATION.md)** - Detailed technical implementation guide
- **[QUICKSTART.md](QUICKSTART.md)** - 30-minute development setup
- **[ROADMAP.md](ROADMAP.md)** - Timeline, milestones, success metrics
- **[claude.md](claude.md)** - Architecture and competitive analysis

---

## 🆚 Why This Fork?

| Feature | This Fork (Future) | laravel-pwa | Other PWA Packages |
|---------|-------------------|-------------|-------------------|
| PWA Manifest | ✅ | ✅ | ✅ |
| Service Worker | ✅ Enhanced | ✅ Basic | ✅ Basic |
| Offline Page | ✅ | ✅ | ✅ |
| **Multiple Cache Strategies** | ✅ | ❌ | ❌ |
| **Background Sync** | ✅ | ❌ | ❌ |
| **Request Queueing** | ✅ | ❌ | ❌ |
| **Form Persistence** | ✅ | ❌ | ❌ |
| **Per-Route Control** | ✅ | ❌ | ❌ |
| **Developer Tools** | ✅ | ❌ | ❌ |
| **Offline Data Caching** | ✅ | ❌ Only offline.html | ❌ |
| **IndexedDB Queue** | ✅ | ❌ | ❌ |

---

## 🎨 Current Features (Base Package)

### Facade Usage

Dynamically update the manifest:

```php
use EragLaravelPwa\Facades\PWA;

PWA::update([
    'name' => 'My Updated App',
    'short_name' => 'MyApp',
    'background_color' => '#ffffff',
    'theme_color' => '#6777ef',
    'icons' => [
        [
            'src' => 'logo.png',
            'sizes' => '512x512',
            'type' => 'image/png',
        ],
    ],
]);
```

### Logo Upload

```php
use EragLaravelPwa\Core\PWA;

public function uploadLogo(Request $request)
{
    $response = PWA::processLogo($request);

    if ($response['status']) {
        return redirect()->back()->with('success', $response['message']);
    }

    return redirect()->back()->withErrors($response['errors']);
}
```

Logo requirements:
- PNG format
- 512x512 minimum
- Max 1024 KB

---

## ⚠️ Important Notes

- **HTTPS Required**: PWAs and service workers require HTTPS in production
- **In Development**: Offline enhancements are actively being built
- **Base Package Works**: All original laravel-pwa functionality is stable
- **Breaking Changes**: May occur during development (pre-v1.0)

---

## 🤝 Contributing

We're actively building this! Contributions welcome:

1. Check [ROADMAP.md](ROADMAP.md) for current priorities
2. Read [IMPLEMENTATION.md](IMPLEMENTATION.md) for technical details
3. Fork and create feature branches
4. Submit PRs with clear descriptions
5. Follow PSR-12 coding standards

### Development Setup

```bash
git clone https://github.com/o-psi/laravel-apps.git
cd laravel-apps

# See QUICKSTART.md for detailed setup instructions
```

---

## 📸 Screenshots (Base Package)

<img width="1470" alt="PWA Install Prompt" src="https://github.com/user-attachments/assets/27c08862-0557-4fbd-bd8f-90b9d05f67b3">

<img width="1470" alt="Installed PWA" src="https://github.com/user-attachments/assets/5e58a596-3267-42d9-98d5-c48b0f54d3ed">

<img width="1470" alt="Offline Page" src="https://github.com/user-attachments/assets/1a80465e-0307-43ac-a1bc-9bca2cf16f8d">

---

## 🙏 Credits

Built on top of [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa) - an excellent foundation for PWA functionality in Laravel.

**Original Package**: erag/laravel-pwa by [Amit Gupta](https://github.com/eramitgupta)

**This Fork**: Enhanced with offline-first capabilities for production use

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file

---

## 🚀 Quick Links

- **Base Package**: [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa)
- **This Fork**: [o-psi/laravel-apps](https://github.com/o-psi/laravel-apps)
- **Issues**: [Report bugs or request features](https://github.com/o-psi/laravel-apps/issues)
- **Discussions**: [Ask questions](https://github.com/o-psi/laravel-apps/discussions)

---

**Status**: 🚧 Active Development | **Target**: Production-ready v1.0 in ~3 months

⭐ Star this repo to follow development progress!
