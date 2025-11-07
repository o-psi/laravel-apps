# Laravel Offline - Implementation Plan

Detailed technical implementation guide for building offline-first functionality on top of laravel-pwa.

## Phase 1: Fork Setup & Enhanced Service Worker (Week 1-2)

### 1.1 Package Fork & Rename

**Files to modify:**
- All PHP files in `src/` directory
- `composer.json`
- `README.md`

**Tasks:**
1. Copy `laravel-pwa-base/` to `src/`
2. Find and replace namespace:
   - `EragLaravelPwa` → `YourVendor\LaravelOffline`
3. Update composer.json:
   ```json
   {
     "name": "yourvendor/laravel-offline",
     "description": "True offline-first functionality for Laravel applications",
     "keywords": ["laravel", "pwa", "offline", "service-worker", "background-sync"],
     "autoload": {
       "psr-4": {
         "YourVendor\\LaravelOffline\\": "src/"
       }
     }
   }
   ```
4. Rename config file: `config/pwa.php` → `config/offline.php`
5. Update all config references in code

### 1.2 Enhanced Service Worker

**Current service worker** (`resources/sw.js`):
- Only has basic offline.html fallback
- No cache strategies
- No runtime caching

**New service worker structure** (`resources/sw.js`):

```javascript
// Cache names
const CACHE_VERSION = '{{CACHE_VERSION}}'; // Replaced by PHP
const CACHE_NAME = `offline-v${CACHE_VERSION}`;
const RUNTIME_CACHE = `runtime-v${CACHE_VERSION}`;
const STRATEGIES = {{{STRATEGIES}}}; // Injected from config

// Installation - precache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll([
        '/offline.html',
        '/logo.png',
        // Add more static assets from config
      ]);
    })
  );
  self.skipWaiting();
});

// Activation - cleanup old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName.startsWith('offline-') && cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
          if (cacheName.startsWith('runtime-') && cacheName !== RUNTIME_CACHE) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch - route to appropriate strategy
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Find matching strategy
  const strategy = findStrategy(url.pathname);

  event.respondWith(
    handleRequest(request, strategy)
  );
});

// Strategy implementations
function handleRequest(request, strategy) {
  switch (strategy) {
    case 'network-first':
      return networkFirst(request);
    case 'cache-first':
      return cacheFirst(request);
    case 'stale-while-revalidate':
      return staleWhileRevalidate(request);
    case 'network-only':
      return fetch(request);
    case 'cache-only':
      return caches.match(request);
    default:
      return networkFirst(request);
  }
}

function networkFirst(request) {
  return fetch(request)
    .then((response) => {
      // Clone and cache successful responses
      if (response.ok) {
        const responseClone = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => {
          cache.put(request, responseClone);
        });
      }
      return response;
    })
    .catch(() => {
      // Fallback to cache
      return caches.match(request).then((cached) => {
        return cached || caches.match('/offline.html');
      });
    });
}

function cacheFirst(request) {
  return caches.match(request).then((cached) => {
    return cached || fetch(request).then((response) => {
      if (response.ok) {
        const responseClone = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => {
          cache.put(request, responseClone);
        });
      }
      return response;
    });
  });
}

function staleWhileRevalidate(request) {
  return caches.match(request).then((cached) => {
    const fetchPromise = fetch(request).then((response) => {
      if (response.ok) {
        const responseClone = response.clone();
        caches.open(RUNTIME_CACHE).then((cache) => {
          cache.put(request, responseClone);
        });
      }
      return response;
    });

    return cached || fetchPromise;
  });
}

function findStrategy(pathname) {
  for (const [pattern, strategy] of Object.entries(STRATEGIES)) {
    const regex = new RegExp(pattern.replace('*', '.*'));
    if (regex.test(pathname)) {
      return strategy;
    }
  }
  return 'network-first';
}
```

**PHP Service Worker Generator** (`src/Services/ServiceWorkerGenerator.php`):

```php
<?php

namespace YourVendor\LaravelOffline\Services;

class ServiceWorkerGenerator
{
    public function generate(): string
    {
        $template = file_get_contents(__DIR__ . '/../../resources/sw.js');

        $cacheVersion = config('offline.cache_version', 1);
        $strategies = $this->formatStrategies();

        $template = str_replace('{{CACHE_VERSION}}', $cacheVersion, $template);
        $template = str_replace('{{{STRATEGIES}}}', json_encode($strategies), $template);

        return $template;
    }

    private function formatStrategies(): array
    {
        $strategies = config('offline.strategies', []);

        // Convert wildcard patterns to regex-compatible format
        return collect($strategies)->mapWithKeys(function ($strategy, $pattern) {
            return [$pattern => $strategy];
        })->toArray();
    }
}
```

### 1.3 Extended Configuration

**New config/offline.php:**

```php
<?php

return [
    'enabled' => env('OFFLINE_ENABLED', true),

    'install-button' => true,

    'cache_version' => env('OFFLINE_CACHE_VERSION', 1),

    'manifest' => [
        'name' => env('APP_NAME', 'Laravel App'),
        'short_name' => env('APP_NAME', 'Laravel'),
        'background_color' => '#ffffff',
        'display' => 'standalone',
        'description' => 'A Progressive Web Application',
        'theme_color' => '#6777ef',
        'icons' => [
            [
                'src' => 'logo.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],
    ],

    // Cache strategies per route pattern
    'strategies' => [
        '/dashboard*' => 'cache-first',
        '/api/*' => 'network-first',
        '/static/*' => 'cache-first',
        '/*.css' => 'cache-first',
        '/*.js' => 'cache-first',
        '/*.png' => 'cache-first',
        '/*.jpg' => 'cache-first',
    ],

    // Cache settings
    'cache' => [
        'max_age' => 86400, // 24 hours
        'max_items' => 100,
        'exclude_query_string' => false,
    ],

    // Background sync settings
    'sync' => [
        'enabled' => true,
        'queue_name' => 'offline-sync',
        'retry_interval' => 5000, // 5 seconds
        'max_retries' => 3,
    ],

    'debug' => env('APP_DEBUG', false),

    'livewire-app' => false,
];
```

---

## Phase 2: Configuration & Middleware (Week 3-4)

### 2.1 Offline Middleware

**Create `src/Middleware/OfflineMiddleware.php`:**

```php
<?php

namespace YourVendor\LaravelOffline\Middleware;

use Closure;
use Illuminate\Http\Request;

class OfflineMiddleware
{
    public function handle(Request $request, Closure $next, ?string $strategy = null, ?int $ttl = null)
    {
        $response = $next($request);

        if (!config('offline.enabled')) {
            return $response;
        }

        // Add cache control headers
        if ($strategy) {
            $response->header('X-Offline-Strategy', $strategy);
        }

        if ($ttl) {
            $response->header('X-Offline-TTL', $ttl);
            $response->header('Cache-Control', "public, max-age={$ttl}");
        }

        // Mark as offline-capable
        $response->header('X-Offline-Enabled', 'true');

        return $response;
    }
}
```

**Register in service provider:**

```php
// src/LaravelOfflineServiceProvider.php
public function boot()
{
    $this->app['router']->aliasMiddleware('offline', OfflineMiddleware::class);

    // ... existing blade directives ...
}
```

### 2.2 New Blade Directives

**Add to service provider:**

```php
// @offlineCache directive
Blade::directive('offlineCache', function ($expression) {
    return "<?php echo app(\\YourVendor\\LaravelOffline\\Services\\OfflineService::class)->cacheStart({$expression}); ?>";
});

Blade::directive('endOfflineCache', function () {
    return "<?php echo app(\\YourVendor\\LaravelOffline\\Services\\OfflineService::class)->cacheEnd(); ?>";
});

// @offlineSync directive
Blade::directive('offlineSync', function ($expression) {
    return "<?php echo app(\\YourVendor\\LaravelOffline\\Services\\OfflineService::class)->syncForm({$expression}); ?>";
});

Blade::directive('endOfflineSync', function () {
    return "<?php echo app(\\YourVendor\\LaravelOffline\\Services\\OfflineService::class)->syncFormEnd(); ?>";
});

// @offlineStatus directive
Blade::directive('offlineStatus', function () {
    return "<?php echo app(\\YourVendor\\LaravelOffline\\Services\\OfflineService::class)->statusIndicator(); ?>";
});
```

**Create `src/Services/OfflineService.php`:**

```php
<?php

namespace YourVendor\LaravelOffline\Services;

class OfflineService
{
    public function cacheStart(string $key): string
    {
        return "<div data-offline-cache=\"{$key}\">";
    }

    public function cacheEnd(): string
    {
        return "</div>";
    }

    public function syncForm(?string $identifier = null): string
    {
        $id = $identifier ?? uniqid('form_');

        return <<<HTML
        <div data-offline-sync="{$id}">
        <script>
        (function() {
            const formId = '{$id}';
            const form = document.querySelector('[data-offline-sync="{$id}"] form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!navigator.onLine) {
                        e.preventDefault();
                        OfflineQueue.add(formId, new FormData(form));
                        alert('You are offline. This form will be submitted when you reconnect.');
                    }
                });
            }
        })();
        </script>
        HTML;
    }

    public function syncFormEnd(): string
    {
        return "</div>";
    }

    public function statusIndicator(): string
    {
        return <<<'HTML'
        <div id="offline-status" style="display: none; position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; background: #f44336; color: white; border-radius: 4px; z-index: 9999;">
            <span id="offline-status-text">Offline</span>
        </div>
        <script>
        window.addEventListener('load', function() {
            const statusEl = document.getElementById('offline-status');
            const statusText = document.getElementById('offline-status-text');

            function updateStatus() {
                if (navigator.onLine) {
                    statusEl.style.display = 'none';
                } else {
                    statusEl.style.display = 'block';
                    statusText.textContent = 'Offline';
                }
            }

            window.addEventListener('online', updateStatus);
            window.addEventListener('offline', updateStatus);
            updateStatus();
        });
        </script>
        HTML;
    }
}
```

---

## Phase 3: Background Sync & Queue (Week 5-6)

### 3.1 IndexedDB Queue Manager

**Create `resources/js/offline-queue.js`:**

```javascript
class OfflineQueue {
  constructor() {
    this.dbName = 'laravel-offline';
    this.storeName = 'sync-queue';
    this.db = null;
    this.init();
  }

  async init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, 1);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve();
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains(this.storeName)) {
          const store = db.createObjectStore(this.storeName, {
            keyPath: 'id',
            autoIncrement: true
          });
          store.createIndex('status', 'status', { unique: false });
          store.createIndex('timestamp', 'timestamp', { unique: false });
        }
      };
    });
  }

  async add(url, method, body, headers = {}) {
    await this.init();

    const transaction = this.db.transaction([this.storeName], 'readwrite');
    const store = transaction.objectStore(this.storeName);

    const item = {
      url,
      method,
      body,
      headers,
      status: 'pending',
      timestamp: Date.now(),
      retries: 0,
    };

    return new Promise((resolve, reject) => {
      const request = store.add(item);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async getAll() {
    await this.init();

    const transaction = this.db.transaction([this.storeName], 'readonly');
    const store = transaction.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async getPending() {
    const all = await this.getAll();
    return all.filter(item => item.status === 'pending');
  }

  async update(id, updates) {
    await this.init();

    const transaction = this.db.transaction([this.storeName], 'readwrite');
    const store = transaction.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const getRequest = store.get(id);

      getRequest.onsuccess = () => {
        const item = getRequest.result;
        Object.assign(item, updates);

        const updateRequest = store.put(item);
        updateRequest.onsuccess = () => resolve(updateRequest.result);
        updateRequest.onerror = () => reject(updateRequest.error);
      };

      getRequest.onerror = () => reject(getRequest.error);
    });
  }

  async delete(id) {
    await this.init();

    const transaction = this.db.transaction([this.storeName], 'readwrite');
    const store = transaction.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const request = store.delete(id);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async clear() {
    await this.init();

    const transaction = this.db.transaction([this.storeName], 'readwrite');
    const store = transaction.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const request = store.clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
}

// Global instance
window.OfflineQueue = new OfflineQueue();
```

### 3.2 Background Sync in Service Worker

**Add to `resources/sw.js`:**

```javascript
// Background Sync
self.addEventListener('sync', (event) => {
  if (event.tag === 'offline-sync') {
    event.waitUntil(syncPendingRequests());
  }
});

async function syncPendingRequests() {
  // This would communicate with the main thread via postMessage
  // to access IndexedDB and process queued requests

  const clients = await self.clients.matchAll();
  if (clients.length === 0) return;

  clients[0].postMessage({
    type: 'SYNC_REQUESTS',
  });
}

// Listen for messages from main thread
self.addEventListener('message', (event) => {
  if (event.data.type === 'QUEUE_REQUEST') {
    // Register for background sync
    self.registration.sync.register('offline-sync');
  }
});
```

### 3.3 Sync Manager

**Create `resources/js/sync-manager.js`:**

```javascript
class SyncManager {
  constructor() {
    this.queue = window.OfflineQueue;
    this.syncing = false;
    this.init();
  }

  init() {
    // Listen for online event
    window.addEventListener('online', () => this.syncAll());

    // Listen for messages from service worker
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data.type === 'SYNC_REQUESTS') {
        this.syncAll();
      }
    });

    // Check if online and sync on load
    if (navigator.onLine) {
      this.syncAll();
    }
  }

  async syncAll() {
    if (this.syncing) return;

    this.syncing = true;
    const pending = await this.queue.getPending();

    for (const item of pending) {
      await this.syncItem(item);
    }

    this.syncing = false;
    this.notifyUser(pending.length);
  }

  async syncItem(item) {
    try {
      const response = await fetch(item.url, {
        method: item.method,
        headers: item.headers,
        body: item.body,
      });

      if (response.ok) {
        await this.queue.update(item.id, { status: 'completed' });
        await this.queue.delete(item.id);
        return true;
      } else {
        await this.handleRetry(item);
        return false;
      }
    } catch (error) {
      await this.handleRetry(item);
      return false;
    }
  }

  async handleRetry(item) {
    const maxRetries = 3;
    const newRetries = item.retries + 1;

    if (newRetries >= maxRetries) {
      await this.queue.update(item.id, {
        status: 'failed',
        retries: newRetries,
      });
    } else {
      await this.queue.update(item.id, {
        retries: newRetries,
      });

      // Retry after delay
      setTimeout(() => this.syncItem(item), 5000 * newRetries);
    }
  }

  notifyUser(count) {
    if (count > 0) {
      console.log(`Synced ${count} offline requests`);

      // Optional: Show notification
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('Offline data synced', {
          body: `${count} request(s) have been synced`,
          icon: '/logo.png',
        });
      }
    }
  }
}

// Initialize
window.SyncManager = new SyncManager();
```

---

## Phase 4: Developer Experience (Week 7-8)

### 4.1 Artisan Commands

**Create `src/Commands/OfflineClearCommand.php`:**

```php
<?php

namespace YourVendor\LaravelOffline\Commands;

use Illuminate\Console\Command;

class OfflineClearCommand extends Command
{
    protected $signature = 'offline:clear';
    protected $description = 'Clear offline cache and increment cache version';

    public function handle()
    {
        $currentVersion = config('offline.cache_version', 1);
        $newVersion = $currentVersion + 1;

        // Update .env file
        $this->updateEnvFile('OFFLINE_CACHE_VERSION', $newVersion);

        $this->info("Cache version updated from {$currentVersion} to {$newVersion}");
        $this->info('Users will receive updated service worker on next visit');

        return 0;
    }

    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                file_get_contents($path)
            ));
        }
    }
}
```

**Create `src/Commands/OfflineStatusCommand.php`:**

```php
<?php

namespace YourVendor\LaravelOffline\Commands;

use Illuminate\Console\Command;

class OfflineStatusCommand extends Command
{
    protected $signature = 'offline:status';
    protected $description = 'Show offline configuration status';

    public function handle()
    {
        $this->info('Offline Configuration Status');
        $this->line('');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', config('offline.enabled') ? '✓' : '✗'],
                ['Cache Version', config('offline.cache_version')],
                ['Install Button', config('offline.install-button') ? '✓' : '✗'],
                ['Background Sync', config('offline.sync.enabled') ? '✓' : '✗'],
                ['Debug Mode', config('offline.debug') ? '✓' : '✗'],
                ['Livewire App', config('offline.livewire-app') ? '✓' : '✗'],
            ]
        );

        $this->line('');
        $this->info('Cache Strategies:');

        $strategies = config('offline.strategies', []);
        $rows = [];

        foreach ($strategies as $pattern => $strategy) {
            $rows[] = [$pattern, $strategy];
        }

        $this->table(['Route Pattern', 'Strategy'], $rows);

        return 0;
    }
}
```

**Create `src/Commands/OfflineRoutesCommand.php`:**

```php
<?php

namespace YourVendor\LaravelOffline\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class OfflineRoutesCommand extends Command
{
    protected $signature = 'offline:routes';
    protected $description = 'Show routes with offline middleware';

    public function handle()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return in_array('offline', $route->middleware());
        });

        if ($routes->isEmpty()) {
            $this->warn('No routes with offline middleware found');
            return 0;
        }

        $this->info('Routes with Offline Middleware:');
        $this->line('');

        $rows = $routes->map(function ($route) {
            return [
                implode('|', $route->methods()),
                $route->uri(),
                $route->getName() ?? '-',
            ];
        })->toArray();

        $this->table(['Method', 'URI', 'Name'], $rows);

        return 0;
    }
}
```

---

## Phase 5: Advanced Features (Week 9-10)

### 5.1 Form Auto-Save

**Create `resources/js/form-persistence.js`:**

```javascript
class FormPersistence {
  constructor(formSelector, options = {}) {
    this.formSelector = formSelector;
    this.storageKey = options.storageKey || `form_${formSelector}`;
    this.debounceTime = options.debounceTime || 500;
    this.excludeFields = options.excludeFields || ['password', 'password_confirmation'];
    this.init();
  }

  init() {
    const form = document.querySelector(this.formSelector);
    if (!form) return;

    // Restore saved data
    this.restore(form);

    // Save on input
    form.addEventListener('input', this.debounce(() => {
      this.save(form);
    }, this.debounceTime));

    // Clear on successful submit
    form.addEventListener('submit', () => {
      setTimeout(() => {
        this.clear();
      }, 1000);
    });
  }

  save(form) {
    const data = {};
    const formData = new FormData(form);

    for (let [key, value] of formData.entries()) {
      if (!this.excludeFields.includes(key)) {
        data[key] = value;
      }
    }

    localStorage.setItem(this.storageKey, JSON.stringify(data));
  }

  restore(form) {
    const saved = localStorage.getItem(this.storageKey);
    if (!saved) return;

    try {
      const data = JSON.parse(saved);

      for (let [key, value] of Object.entries(data)) {
        const input = form.querySelector(`[name="${key}"]`);
        if (input) {
          input.value = value;
        }
      }
    } catch (error) {
      console.error('Failed to restore form data:', error);
    }
  }

  clear() {
    localStorage.removeItem(this.storageKey);
  }

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }
}

// Auto-initialize forms with data-persist attribute
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-persist]').forEach((form) => {
    const storageKey = form.dataset.persist || undefined;
    new FormPersistence(`#${form.id}`, { storageKey });
  });
});

window.FormPersistence = FormPersistence;
```

### 5.2 Cache Inspector Component

**Create Blade component `resources/views/components/cache-inspector.blade.php`:**

```blade
<div id="offline-cache-inspector" style="position: fixed; bottom: 0; right: 0; width: 400px; background: white; border: 1px solid #ccc; border-radius: 8px 8px 0 0; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 9998; transform: translateY(100%); transition: transform 0.3s;">
    <div style="padding: 16px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="toggleCacheInspector()">
        <strong>Offline Cache Inspector</strong>
        <span style="float: right;">▲</span>
    </div>
    <div id="cache-inspector-content" style="padding: 16px; max-height: 400px; overflow-y: auto;">
        <div id="cache-stats"></div>
        <div id="cache-entries"></div>
        <div id="queue-entries"></div>
    </div>
</div>

<script>
let inspectorOpen = false;

function toggleCacheInspector() {
    const inspector = document.getElementById('offline-cache-inspector');
    inspectorOpen = !inspectorOpen;
    inspector.style.transform = inspectorOpen ? 'translateY(0)' : 'translateY(calc(100% - 50px))';

    if (inspectorOpen) {
        loadCacheStats();
    }
}

async function loadCacheStats() {
    const cacheNames = await caches.keys();
    const stats = document.getElementById('cache-stats');

    stats.innerHTML = `<strong>Caches:</strong> ${cacheNames.length}<br>`;

    for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const keys = await cache.keys();
        stats.innerHTML += `- ${cacheName}: ${keys.length} items<br>`;
    }

    // Load queue stats
    const pending = await window.OfflineQueue.getPending();
    const queueStats = document.getElementById('queue-entries');
    queueStats.innerHTML = `<br><strong>Pending Sync Requests:</strong> ${pending.length}<br>`;

    pending.forEach(item => {
        queueStats.innerHTML += `- ${item.method} ${item.url} (${item.retries} retries)<br>`;
    });
}

// Auto-open in debug mode
@if(config('offline.debug'))
setTimeout(() => toggleCacheInspector(), 1000);
@endif
</script>
```

---

## Testing Strategy

### Unit Tests

**Create `tests/Unit/ServiceWorkerGeneratorTest.php`:**
```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use YourVendor\LaravelOffline\Services\ServiceWorkerGenerator;

class ServiceWorkerGeneratorTest extends TestCase
{
    public function test_generates_service_worker_with_strategies()
    {
        config(['offline.strategies' => [
            '/api/*' => 'network-first',
            '/dashboard*' => 'cache-first',
        ]]);

        $generator = new ServiceWorkerGenerator();
        $sw = $generator->generate();

        $this->assertStringContainsString('network-first', $sw);
        $this->assertStringContainsString('cache-first', $sw);
    }
}
```

### Feature Tests

**Create `tests/Feature/OfflineMiddlewareTest.php`:**
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class OfflineMiddlewareTest extends TestCase
{
    public function test_middleware_adds_offline_headers()
    {
        $response = $this->get('/test-route');

        $response->assertHeader('X-Offline-Enabled', 'true');
    }
}
```

---

## Documentation Requirements

1. **README.md** - Installation, basic usage, features
2. **INSTALLATION.md** - Detailed installation guide
3. **CONFIGURATION.md** - All config options explained
4. **CACHE-STRATEGIES.md** - When to use each strategy
5. **API.md** - JavaScript API reference
6. **EXAMPLES.md** - Real-world examples
7. **TROUBLESHOOTING.md** - Common issues and solutions
8. **CONTRIBUTING.md** - How to contribute

---

## Marketing & Launch

1. **Package Name**: `yourvendor/laravel-offline`
2. **Tagline**: "True offline-first functionality for Laravel - not just a manifest generator"
3. **Demo App**: Build a todo app that works offline
4. **Video Tutorial**: 5-minute setup and demo
5. **Blog Post**: "Building Offline-First Laravel Apps in 2025"
6. **Submit to**:
   - Packagist (automatic via GitHub)
   - Laravel News
   - Reddit r/laravel
   - Twitter/X with #Laravel hashtag
   - Dev.to
   - Medium

---

## Success Metrics

- ⭐ 100 stars in first month
- ⭐ 500 stars in 6 months
- 📦 1000+ installs in first 6 months
- 💬 Active community discussions
- 🐛 Issues being reported and resolved
- 🎯 Featured in Laravel News or similar

---

## Next Steps

1. Choose vendor name and package name
2. Fork and rename the package
3. Start with Phase 1: Enhanced Service Worker
4. Build demo app alongside development
5. Write tests as you go
6. Document features as you build
7. Release v0.1.0 with basic functionality
8. Iterate based on feedback
