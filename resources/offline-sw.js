"use strict";

/**
 * Laravel Offline - Enhanced Service Worker
 *
 * Provides advanced caching strategies, route matching, and offline capabilities.
 * Configuration is injected at runtime from Laravel config.
 */

// Configuration will be injected by Laravel
const CONFIG = {
    cacheVersion: 1,
    debug: false,
    networkTimeout: 3000,
    cacheNames: {
        pages: 'offline-pages',
        assets: 'offline-assets',
        api: 'offline-api',
        runtime: 'offline-runtime'
    },
    strategies: {},
    defaultStrategy: 'network-first',
    precache: ['/offline.html'],
    assetPatterns: ['*.js', '*.css', '*.woff', '*.woff2', '*.ttf', '*.eot', '*.svg', '*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp'],
    maxAge: 86400,
    maxItems: 100
};

// Generate versioned cache names
const CACHE_NAMES = {
    pages: `${CONFIG.cacheNames.pages}-v${CONFIG.cacheVersion}`,
    assets: `${CONFIG.cacheNames.assets}-v${CONFIG.cacheVersion}`,
    api: `${CONFIG.cacheNames.api}-v${CONFIG.cacheVersion}`,
    runtime: `${CONFIG.cacheNames.runtime}-v${CONFIG.cacheVersion}`
};

const OFFLINE_URL = '/offline.html';

/**
 * Logging utility
 */
function log(...args) {
    if (CONFIG.debug) {
        console.log('[Offline SW]', ...args);
    }
}

/**
 * Check if URL matches a pattern (supports wildcards)
 */
function matchesPattern(url, pattern) {
    const urlPath = new URL(url).pathname;
    const regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$');
    return regex.test(urlPath);
}

/**
 * Check if URL is an asset (based on extension)
 */
function isAsset(url) {
    const urlPath = new URL(url).pathname;
    return CONFIG.assetPatterns.some(pattern => {
        const ext = pattern.replace('*.', '\\.');
        const regex = new RegExp(ext + '$', 'i');
        return regex.test(urlPath);
    });
}

/**
 * Get cache strategy for a given URL
 */
function getStrategy(url) {
    // Check if it's an asset
    if (isAsset(url)) {
        return 'cache-first';
    }

    // Check configured route patterns
    for (const [pattern, strategy] of Object.entries(CONFIG.strategies)) {
        if (matchesPattern(url, pattern)) {
            log('Matched pattern', pattern, 'with strategy', strategy, 'for', url);
            return strategy;
        }
    }

    // Return default strategy
    return CONFIG.defaultStrategy;
}

/**
 * Get appropriate cache name based on request type
 */
function getCacheName(request) {
    const url = request.url;

    if (isAsset(url)) {
        return CACHE_NAMES.assets;
    }

    if (url.includes('/api/')) {
        return CACHE_NAMES.api;
    }

    if (request.mode === 'navigate') {
        return CACHE_NAMES.pages;
    }

    return CACHE_NAMES.runtime;
}

/**
 * Check if cached response is still fresh
 */
function isCacheFresh(cachedResponse) {
    if (!cachedResponse) return false;

    const cachedTime = cachedResponse.headers.get('sw-cached-time');
    if (!cachedTime) return true; // No timestamp, assume fresh

    const age = Date.now() - parseInt(cachedTime);
    return age < (CONFIG.maxAge * 1000);
}

/**
 * Add metadata headers to response before caching
 */
function addCacheMetadata(response) {
    const headers = new Headers(response.headers);
    headers.set('sw-cached-time', Date.now().toString());

    return new Response(response.body, {
        status: response.status,
        statusText: response.statusText,
        headers: headers
    });
}

/**
 * Enforce cache size limits
 */
async function enforceCacheLimits(cacheName) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();

    if (keys.length > CONFIG.maxItems) {
        // Remove oldest entries (FIFO)
        const toDelete = keys.length - CONFIG.maxItems;
        for (let i = 0; i < toDelete; i++) {
            await cache.delete(keys[i]);
            log('Deleted old cache entry:', keys[i].url);
        }
    }
}

/**
 * Cache-first strategy: Try cache, fall back to network
 */
async function cacheFirst(request) {
    const cacheName = getCacheName(request);
    const cachedResponse = await caches.match(request);

    if (cachedResponse && isCacheFresh(cachedResponse)) {
        log('Cache hit (fresh):', request.url);
        return cachedResponse;
    }

    if (cachedResponse) {
        log('Cache hit (stale):', request.url);
    } else {
        log('Cache miss, fetching:', request.url);
    }

    try {
        const networkResponse = await fetch(request);

        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            const responseToCache = await addCacheMetadata(networkResponse.clone());
            await cache.put(request, responseToCache);
            await enforceCacheLimits(cacheName);
        }

        return networkResponse;
    } catch (error) {
        log('Network fetch failed:', error);

        // Return stale cache if available
        if (cachedResponse) {
            log('Returning stale cache due to network error');
            return cachedResponse;
        }

        // For navigation requests, return offline page
        if (request.mode === 'navigate') {
            const offlineResponse = await caches.match(OFFLINE_URL);
            if (offlineResponse) {
                return offlineResponse;
            }
        }

        throw error;
    }
}

/**
 * Network-first strategy: Try network, fall back to cache
 */
async function networkFirst(request) {
    const cacheName = getCacheName(request);

    try {
        const timeoutPromise = new Promise((_, reject) => {
            setTimeout(() => reject(new Error('Network timeout')), CONFIG.networkTimeout);
        });

        const networkResponse = await Promise.race([
            fetch(request),
            timeoutPromise
        ]);

        // Cache successful responses
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            const responseToCache = await addCacheMetadata(networkResponse.clone());
            await cache.put(request, responseToCache);
            await enforceCacheLimits(cacheName);
        }

        log('Network success:', request.url);
        return networkResponse;
    } catch (error) {
        log('Network failed, trying cache:', error);

        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            const fresh = isCacheFresh(cachedResponse);
            log('Cache hit after network failure (' + (fresh ? 'fresh' : 'stale') + '):', request.url);
            return cachedResponse;
        }

        // For navigation requests, return offline page
        if (request.mode === 'navigate') {
            const offlineResponse = await caches.match(OFFLINE_URL);
            if (offlineResponse) {
                return offlineResponse;
            }
        }

        throw error;
    }
}

/**
 * Stale-while-revalidate: Return cached response immediately,
 * fetch fresh version in background
 */
async function staleWhileRevalidate(request) {
    const cacheName = getCacheName(request);
    const cachedResponse = await caches.match(request);

    // Fetch fresh version in background
    const fetchPromise = fetch(request).then(async networkResponse => {
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            const responseToCache = await addCacheMetadata(networkResponse.clone());
            await cache.put(request, responseToCache);
            await enforceCacheLimits(cacheName);
        }
        return networkResponse;
    }).catch(error => {
        log('Background fetch failed:', error);
        return null;
    });

    // Return cached response immediately if available
    if (cachedResponse) {
        const fresh = isCacheFresh(cachedResponse);
        log('Serving ' + (fresh ? 'fresh' : 'stale') + ' cache, revalidating:', request.url);
        return cachedResponse;
    }

    // Otherwise wait for network
    log('No cache, waiting for network:', request.url);
    return fetchPromise;
}

/**
 * Network-only strategy: Always fetch from network
 */
async function networkOnly(request) {
    log('Network only:', request.url);
    try {
        return await fetch(request);
    } catch (error) {
        // For navigation requests, return offline page
        if (request.mode === 'navigate') {
            const offlineResponse = await caches.match(OFFLINE_URL);
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        throw error;
    }
}

/**
 * Cache-only strategy: Only serve from cache
 */
async function cacheOnly(request) {
    log('Cache only:', request.url);
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    // For navigation requests, return offline page
    if (request.mode === 'navigate') {
        const offlineResponse = await caches.match(OFFLINE_URL);
        if (offlineResponse) {
            return offlineResponse;
        }
    }

    throw new Error('Not found in cache');
}

/**
 * Route request to appropriate strategy
 */
async function handleRequest(request) {
    const strategy = getStrategy(request.url);
    log('Using strategy:', strategy, 'for', request.url);

    switch (strategy) {
        case 'cache-first':
            return cacheFirst(request);
        case 'network-first':
            return networkFirst(request);
        case 'stale-while-revalidate':
            return staleWhileRevalidate(request);
        case 'network-only':
            return networkOnly(request);
        case 'cache-only':
            return cacheOnly(request);
        default:
            log('Unknown strategy, using network-first:', strategy);
            return networkFirst(request);
    }
}

/**
 * Install event: Precache essential files
 */
self.addEventListener('install', (event) => {
    log('Service worker installing...');

    event.waitUntil(
        caches.open(CACHE_NAMES.pages)
            .then((cache) => {
                log('Precaching:', CONFIG.precache);
                return cache.addAll(CONFIG.precache);
            })
            .then(() => {
                log('Service worker installed');
                return self.skipWaiting();
            })
            .catch((error) => {
                log('Installation failed:', error);
            })
    );
});

/**
 * Activate event: Clean up old caches
 */
self.addEventListener('activate', (event) => {
    log('Service worker activating...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                const validCacheNames = Object.values(CACHE_NAMES);

                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (!validCacheNames.includes(cacheName)) {
                            log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                log('Service worker activated');
                return self.clients.claim();
            })
    );
});

/**
 * Fetch event: Handle all network requests
 */
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        log('Ignoring non-GET request:', event.request.method, event.request.url);
        return;
    }

    // Skip chrome extension requests
    if (event.request.url.startsWith('chrome-extension://')) {
        return;
    }

    event.respondWith(handleRequest(event.request));
});

/**
 * Message event: Handle messages from clients
 */
self.addEventListener('message', (event) => {
    log('Received message:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        log('Clearing cache:', cacheName);
                        return caches.delete(cacheName);
                    })
                );
            })
        );
    }

    if (event.data && event.data.type === 'QUEUE_REQUEST') {
        // Queue a request for background sync
        const { url, method, headers, body } = event.data.request;

        event.waitUntil(
            queueRequest(url, method, headers, body)
                .then(() => {
                    log('Request queued for sync:', method, url);
                    if (event.ports && event.ports[0]) {
                        event.ports[0].postMessage({ success: true });
                    }
                })
                .catch((error) => {
                    log('Failed to queue request:', error);
                    if (event.ports && event.ports[0]) {
                        event.ports[0].postMessage({ success: false, error: error.message });
                    }
                })
        );
    }
});

/**
 * Background Sync event: Process queued requests
 */
if ('sync' in self.registration) {
    self.addEventListener('sync', (event) => {
        log('Background sync event:', event.tag);

        if (event.tag === 'offline-sync') {
            event.waitUntil(processBackgroundSync());
        }
    });
}

/**
 * Queue a request in IndexedDB for later sync
 */
async function queueRequest(url, method, headers, body) {
    // Open IndexedDB
    const db = await openQueueDB();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['requests'], 'readwrite');
        const store = transaction.objectStore('requests');

        const request = {
            url,
            method,
            headers,
            body,
            timestamp: Date.now(),
            retries: 0
        };

        const addRequest = store.add(request);

        addRequest.onsuccess = () => {
            log('Request added to queue:', request.id);

            // Register background sync
            if ('sync' in self.registration) {
                self.registration.sync.register('offline-sync')
                    .then(() => log('Background sync registered'))
                    .catch((error) => log('Failed to register sync:', error));
            }

            resolve();
        };

        addRequest.onerror = () => reject(addRequest.error);
    });
}

/**
 * Open IndexedDB for queue storage
 */
function openQueueDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('offline-queue', 1);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('requests')) {
                const store = db.createObjectStore('requests', {
                    keyPath: 'id',
                    autoIncrement: true
                });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

/**
 * Process all queued requests during background sync
 */
async function processBackgroundSync() {
    log('Processing background sync...');

    const db = await openQueueDB();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['requests'], 'readwrite');
        const store = transaction.objectStore('requests');
        const getAllRequest = store.getAll();

        getAllRequest.onsuccess = async () => {
            const requests = getAllRequest.result;
            log('Found', requests.length, 'queued requests');

            const results = [];

            for (const request of requests) {
                try {
                    const response = await fetch(request.url, {
                        method: request.method,
                        headers: request.headers,
                        body: request.body
                    });

                    if (response.ok) {
                        // Success - remove from queue
                        const deleteTransaction = db.transaction(['requests'], 'readwrite');
                        const deleteStore = deleteTransaction.objectStore('requests');
                        deleteStore.delete(request.id);

                        log('Synced request:', request.method, request.url);
                        results.push({ success: true, request });
                    } else {
                        // Server error - update retry count
                        request.retries++;
                        const updateTransaction = db.transaction(['requests'], 'readwrite');
                        const updateStore = updateTransaction.objectStore('requests');
                        updateStore.put(request);

                        log('Request failed, will retry:', request.url);
                        results.push({ success: false, request, status: response.status });
                    }
                } catch (error) {
                    // Network error - keep in queue
                    log('Network error during sync:', error);
                    results.push({ success: false, request, error: error.message });
                }
            }

            // Notify all clients about sync completion
            const clients = await self.clients.matchAll();
            clients.forEach(client => {
                client.postMessage({
                    type: 'SYNC_COMPLETE',
                    results
                });
            });

            resolve();
        };

        getAllRequest.onerror = () => reject(getAllRequest.error);
    });
}

log('Service worker script loaded');
