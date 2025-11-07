# Laravel Offline

<div align="center">

> **The foundation of professional offline-first Laravel development**

[![License](https://img.shields.io/badge/Licence-MIT-blue)](LICENSE)
[![Status](https://img.shields.io/badge/Status-In%20Development-orange)](https://github.com/o-psi/laravel-apps)
[![Laravel](https://img.shields.io/badge/Laravel-8.x%20to%2012.x-red)](https://laravel.com)

**[Documentation](#-documentation) • [Quick Start](#-quick-start) • [Roadmap](#-development-roadmap) • [Community](#-community)**

</div>

---

## 🏢 Part of the o-psi Ecosystem

Laravel Offline is the **flagship package** in a growing family of professional Laravel tools designed for production applications. We're building a comprehensive ecosystem of interconnected packages, premium components, and educational resources - all focused on making Laravel development more powerful and profitable.

**Current Projects:**
- **🔄 Laravel Offline** (this package) - Production-ready offline-first functionality
- **📦 More coming soon** - Additional professional tools in active development

**The Ecosystem:**
- 📚 Comprehensive documentation sites
- 🎓 Premium screencasts and courses (planned)
- 🧩 Pre-built commercial components (planned)
- 💬 Dedicated community support channels
- 🎤 Conference talks and workshops (planned)

---

## 🎯 Vision

Transform your Laravel applications into powerful offline-first experiences. Laravel Offline provides intelligent caching, background sync, and request queueing so your apps work seamlessly when users lose connectivity.

**Not just a PWA manifest generator** - this is a complete, production-ready offline solution for modern Laravel applications, backed by professional support and ongoing development.

## 📊 Current Status: **Active Development**

**Available Now**: ✅ Core PWA functionality, manifest generation, service workers

**Coming Soon**: 🚧 Advanced offline features (see roadmap below)

---

## 🎓 Professional Development, Sustainable Growth

Laravel Offline isn't just another side project - it's the foundation of a professional ecosystem built for the long term.

### Our Approach

**🔓 Open Core Model**
- Core packages are **free and MIT licensed** forever
- Premium features and components available for commercial users
- No bait-and-switch - clear separation between free and paid

**📚 Education-First**
- Comprehensive documentation for all skill levels
- Free tutorials and getting-started guides
- Premium screencasts for advanced techniques
- Real-world examples and use cases

**🤝 Community-Driven**
- Active maintenance and rapid bug fixes
- Regular feature releases based on community feedback
- Transparent roadmap and development process
- Direct access to maintainers

**💼 Production-Ready**
- Battle-tested code patterns
- Comprehensive test coverage
- Professional support options (planned)
- Enterprise consulting available (planned)

### Why This Matters

This sustainable approach means:
- ✅ Reliable long-term maintenance
- ✅ Continuous improvement and new features
- ✅ Professional support when you need it
- ✅ Growing ecosystem of compatible tools
- ✅ Educational resources that keep you ahead

We're not building a package - we're building a **platform** for offline-first Laravel development.

---

## ✨ Features

### ✅ Available Now

- ⚙️ Auto-generate PWA manifest and service worker files
- 🧩 Configurable "Add to Home Screen" install prompt
- 📱 Fully responsive - works on mobile and desktop
- 🛠️ Customizable via `config/pwa.php`
- 🧑‍💻 Blade directives (`@PwaHead`, `@RegisterServiceWorkerScript`)
- 🔐 HTTPS ready
- 🌐 Compatible with Blade, Livewire, Vue 3, and React
- 🔄 Supports Laravel 8.x to 12.x

### 🚀 Coming Soon

- **Advanced Caching Strategies** - cache-first, network-first, stale-while-revalidate per route
- **Background Sync** - queue requests when offline, auto-sync when online
- **IndexedDB Storage** - reliable offline data storage with automatic retry
- **Per-Route Control** - configure cache behavior via middleware or config
- **Form Persistence** - auto-save forms to localStorage, never lose user data
- **Developer Tools** - cache inspector, Artisan commands, debug panel
- **Zero Config** - works out of the box, powerful when configured
- **New Directives** - `@offlineCache`, `@offlineSync`, `@offlineStatus`

---

## 📦 Installation

```bash
composer require erag/laravel-pwa
```

### Laravel 11.x, 12.x

Register in `/bootstrap/providers.php`:

```php
use EragLaravelPwa\EragLaravelPwaServiceProvider;

return [
    // ...
    EragLaravelPwaServiceProvider::class,
];
```

### Laravel 8.x, 9.x, 10.x

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

### 1. Add to Your Layout

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

```bash
php artisan erag:update-manifest
```

That's it! Your app is now a Progressive Web Application.

---

## 🎯 Upcoming Features

The following features are in active development. See [OFFLINE_PLAN.md](OFFLINE_PLAN.md) for complete specifications.

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

When offline, forms are queued and submitted automatically when connection returns.

### Form Auto-Save

```blade
<form id="my-form" data-persist>
    <input type="text" name="title">
    <textarea name="content"></textarea>
    <button type="submit">Save</button>
</form>
```

Never lose user data - forms auto-save to localStorage on every keystroke.

### Cache Strategies

```php
// config/offline.php (coming soon)
'strategies' => [
    '/dashboard*' => 'cache-first',
    '/api/*' => 'network-first',
    '/static/*' => 'cache-first',
],
```

Different caching strategies for different routes.

---

## 🗺️ Development Roadmap

### Phase 1: Enhanced Service Worker (Weeks 1-2)
- [ ] Multiple cache strategies (network-first, cache-first, stale-while-revalidate)
- [ ] Route pattern matching
- [ ] Cache versioning and auto-cleanup
- [ ] Runtime API caching

### Phase 2: Configuration & Middleware (Weeks 3-4)
- [ ] Offline middleware for per-route control
- [ ] Extended configuration system
- [ ] New Blade directives
- [ ] Cache TTL and size limits

### Phase 3: Background Sync (Weeks 5-6)
- [ ] IndexedDB queue manager
- [ ] Request queueing (POST/PUT/DELETE)
- [ ] Auto-retry with exponential backoff
- [ ] Form auto-save to localStorage

### Phase 4: Developer Tools (Weeks 7-8)
- [ ] Cache inspector UI component
- [ ] Artisan commands (`offline:clear`, `offline:status`)
- [ ] Debug toolbar integration
- [ ] Performance metrics dashboard

### Phase 5: Production Ready (Weeks 9-12)
- [ ] Conflict resolution strategies
- [ ] File upload queueing with progress
- [ ] Multi-tab synchronization
- [ ] Comprehensive test suite
- [ ] v1.0.0 release

**Timeline**: ~3 months to production-ready v1.0

See [ROADMAP.md](ROADMAP.md) for detailed milestones and success metrics.

---

## 📚 Documentation

- **[OFFLINE_PLAN.md](OFFLINE_PLAN.md)** - Complete vision and planned features
- **[IMPLEMENTATION.md](IMPLEMENTATION.md)** - Detailed technical implementation guide
- **[QUICKSTART.md](QUICKSTART.md)** - 30-minute development setup guide
- **[ROADMAP.md](ROADMAP.md)** - Timeline, milestones, and success metrics
- **[claude.md](claude.md)** - Architecture and competitive analysis

---

## 🆚 Why Laravel Offline?

### More Than Just Features

| Feature | Laravel Offline | Other PWA Packages |
|---------|----------------|-------------------|
| PWA Manifest | ✅ | ✅ |
| Service Worker | ✅ Enhanced | ✅ Basic |
| Offline Page | ✅ | ✅ |
| **Multiple Cache Strategies** | ✅ | ❌ |
| **Background Sync** | ✅ | ❌ |
| **Request Queueing** | ✅ | ❌ |
| **Form Persistence** | ✅ | ❌ |
| **Per-Route Control** | ✅ | ❌ |
| **Developer Tools** | ✅ | ❌ |
| **Offline Data Caching** | ✅ | ❌ |
| **IndexedDB Queue** | ✅ | ❌ |

### The Professional Difference

| Aspect | Laravel Offline Ecosystem | Typical Packages |
|--------|--------------------------|------------------|
| **Maintenance** | Professional, full-time | Hobby/part-time |
| **Documentation** | Comprehensive + video courses | Basic README |
| **Support** | Active community + paid options | GitHub issues only |
| **Ecosystem** | Growing family of tools | Single package |
| **Long-term** | Sustainable business model | Unknown future |
| **Education** | Courses, tutorials, articles | Limited resources |
| **Components** | Premium UI components available | None |
| **Updates** | Regular feature releases | Sporadic updates |

**Choose Laravel Offline for:**
- 🏢 **Production applications** that need reliable, maintained code
- 📈 **Growing businesses** that want an ecosystem that grows with them
- 🎓 **Learning teams** who benefit from comprehensive educational resources
- 💼 **Enterprise users** who need professional support options
- 🚀 **Forward-thinking developers** who want to be part of something bigger

---

## 🎨 Current Features

### Dynamic Manifest Updates

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

**Logo Requirements**:
- PNG format
- 512x512 pixels minimum
- Maximum 1024 KB

---

## ⚠️ Important Notes

- **HTTPS Required**: PWAs and service workers require HTTPS in production
- **Active Development**: Advanced features are being actively developed
- **Stable Base**: Core PWA functionality is production-ready
- **Breaking Changes**: May occur before v1.0

---

## 🎯 Use Cases

Perfect for:

- **SaaS Applications** - Keep users productive during connectivity issues
- **Field Service Apps** - Technicians and sales reps working in low-connectivity areas
- **Mobile-First Apps** - Ensure smooth experience on unstable mobile networks
- **Data Collection Forms** - Never lose user input, even offline
- **E-commerce** - Let users browse and add to cart offline, sync later
- **Content Apps** - Read articles and content without internet

---

## 🤝 Contributing

We're building the future of offline-first Laravel development, and we'd love your help!

### Ways to Contribute

**Code Contributions:**
1. Check [ROADMAP.md](ROADMAP.md) for current priorities
2. Read [IMPLEMENTATION.md](IMPLEMENTATION.md) for technical details
3. Fork and create feature branches
4. Submit PRs with clear descriptions
5. Follow PSR-12 coding standards

**Non-Code Contributions:**
- 📝 Improve documentation
- 🎨 Design UI components or assets
- 🐛 Report bugs with detailed reproduction steps
- 💡 Suggest features in GitHub Discussions
- ⭐ Star the repo and spread the word
- 📢 Write tutorials or blog posts
- 🎥 Create video content

### Development Setup

```bash
git clone https://github.com/o-psi/laravel-apps.git
cd laravel-apps

# See QUICKSTART.md for detailed setup instructions
```

### Contributor Benefits

As the ecosystem grows, active contributors will:
- Get early access to premium features
- Be featured in project showcases
- Receive swag and recognition
- Join our contributor Discord channels
- Get free access to paid courses

We believe in rewarding those who help build the future with us.

---

## 📸 Screenshots

### PWA Install Prompt
<img width="1470" alt="PWA Install Prompt" src="https://github.com/user-attachments/assets/27c08862-0557-4fbd-bd8f-90b9d05f67b3">

### Installed as Native App
<img width="1470" alt="Installed PWA" src="https://github.com/user-attachments/assets/5e58a596-3267-42d9-98d5-c48b0f54d3ed">

### Offline Fallback
<img width="1470" alt="Offline Page" src="https://github.com/user-attachments/assets/1a80465e-0307-43ac-a1bc-9bca2cf16f8d">

---

## 🚀 Quick Links

**Development:**
- **Repository**: [o-psi/laravel-apps](https://github.com/o-psi/laravel-apps)
- **Issues**: [Report bugs or request features](https://github.com/o-psi/laravel-apps/issues)
- **Discussions**: [Ask questions and share ideas](https://github.com/o-psi/laravel-apps/discussions)
- **Pull Requests**: [Contribute code](https://github.com/o-psi/laravel-apps/pulls)

**Documentation:**
- **[OFFLINE_PLAN.md](OFFLINE_PLAN.md)** - Complete vision and features
- **[IMPLEMENTATION.md](IMPLEMENTATION.md)** - Technical implementation guide
- **[ROADMAP.md](ROADMAP.md)** - Timeline and milestones
- **[QUICKSTART.md](QUICKSTART.md)** - 30-minute setup guide

**Community** (coming soon):
- **Discord** - Real-time support and discussions
- **Twitter** - Updates and announcements
- **Newsletter** - Monthly offline-first tips
- **YouTube** - Video tutorials
- **Blog** - In-depth articles

---

## 💡 The Complete Ecosystem

Laravel Offline is just the beginning. We're building a comprehensive suite of professional tools:

### 🎯 Current Focus: Laravel Offline
The flagship package providing production-ready offline-first functionality for Laravel applications. This is our foundation - rock-solid, well-documented, and actively maintained.

### 🚀 Planned Expansions

**Free & Open Source:**
- **Laravel Offline UI** - Pre-built, customizable UI components for offline features
- **Laravel Offline Admin** - Admin panel with offline capabilities built-in
- **Laravel Offline Analytics** - Track and analyze offline usage patterns

**Premium Offerings:**
- **Laravel Offline Pro** - Advanced features, priority support, commercial license
- **Offline-First Components** - Pre-built, production-ready components (dashboards, forms, data tables)
- **Video Courses** - Deep-dive screencasts teaching offline-first architecture
- **Starter Kits** - Complete offline-first application templates

### 💰 Sustainable Open Source

We believe in building sustainable open source:
- Core packages remain **free and MIT licensed**
- Premium products and support fund ongoing development
- Commercial components help businesses move faster
- Educational content serves both learning and revenue

This model allows us to invest full-time in making Laravel development better.

---

## 🌟 Community

Building software is better together. Join our growing community:

- **💬 Discord** (coming soon) - Real-time help and discussions
- **🐦 Twitter** (coming soon) - Updates, tips, and announcements
- **📧 Newsletter** (coming soon) - Monthly updates and offline-first tips
- **📺 YouTube** (planned) - Free tutorials and feature showcases
- **📝 Blog** (planned) - In-depth articles on offline-first development

**Current Channels:**
- **GitHub Discussions**: [Ask questions and share ideas](https://github.com/o-psi/laravel-apps/discussions)
- **Issues**: [Report bugs or request features](https://github.com/o-psi/laravel-apps/issues)

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

This package builds upon the excellent foundation provided by [eramitgupta/laravel-pwa](https://github.com/eramitgupta/laravel-pwa). We're grateful to Amit Gupta and all contributors for their work on the base PWA functionality.

---

---

## 📈 Project Status & Timeline

**Current Phase**: 🚧 Active Development
**Target**: v1.0 Release in Q1 2025
**Long-term**: Building a complete ecosystem of professional Laravel tools

### Milestones
- ✅ Core PWA functionality (complete)
- 🚧 Advanced offline features (in progress)
- 📅 v1.0.0 production release (Q1 2025)
- 📅 Premium components launch (Q2 2025)
- 📅 First video course (Q2 2025)
- 📅 Community platform launch (Q3 2025)

---

## ⭐ Support the Project

Laravel Offline is **free and open source**, but it takes significant time and effort to build and maintain. Here's how you can support:

- ⭐ **Star this repository** - helps others discover the project
- 🐦 **Follow on Twitter** (coming soon) - stay updated with news
- 📢 **Share with your network** - spread the word
- 💰 **Sponsor development** (coming soon) - fund full-time work
- 🛒 **Buy premium products** (planned) - support through purchases
- 🤝 **Contribute code or docs** - help build the future

Every bit helps us build better tools for the Laravel community!

---

<div align="center">

## 🚀 Ready to Build Offline-First?

**[Get Started](#-installation)** • **[Read the Docs](#-documentation)** • **[Join Community](#-community)**

---

**Status**: 🚧 Active Development | **License**: MIT | **Made with ❤️ for Laravel**

**Laravel Offline** - Foundation of the o-psi ecosystem

⭐ Star this repo to be part of the offline-first revolution!

</div>
