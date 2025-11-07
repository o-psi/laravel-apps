/**
 * Laravel Offline - IndexedDB Queue Manager
 *
 * Manages offline request queue using IndexedDB for reliable storage.
 * Automatically syncs queued requests when connection returns.
 */

(function() {
    'use strict';

    const DB_NAME = 'offline-queue';
    const DB_VERSION = 1;
    const STORE_NAME = 'requests';
    const DEBUG = false;

    let db = null;

    function log(...args) {
        if (DEBUG) {
            console.log('[Queue Manager]', ...args);
        }
    }

    /**
     * Initialize IndexedDB
     */
    function initDB() {
        return new Promise((resolve, reject) => {
            if (db) {
                resolve(db);
                return;
            }

            // Check if IndexedDB is available
            if (!window.indexedDB) {
                const error = new Error('IndexedDB is not supported in this browser');
                console.error('[Queue Manager]', error.message);
                reject(error);
                return;
            }

            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => {
                const error = request.error;
                console.error('[Queue Manager] Failed to open database:', error);

                // Check for quota exceeded error
                if (error.name === 'QuotaExceededError') {
                    console.error('[Queue Manager] Storage quota exceeded. Please free up space.');
                }

                reject(error);
            };

            request.onsuccess = () => {
                db = request.result;

                // Handle database errors after opening
                db.onerror = (event) => {
                    console.error('[Queue Manager] Database error:', event.target.error);
                };

                log('Database initialized');
                resolve(db);
            };

            request.onupgradeneeded = (event) => {
                const database = event.target.result;

                // Create object store
                if (!database.objectStoreNames.contains(STORE_NAME)) {
                    const store = database.createObjectStore(STORE_NAME, {
                        keyPath: 'id',
                        autoIncrement: true
                    });

                    store.createIndex('timestamp', 'timestamp', { unique: false });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('retries', 'retries', { unique: false });

                    log('Object store created');
                }
            };

            request.onblocked = () => {
                console.warn('[Queue Manager] Database upgrade blocked. Close other tabs with this site.');
            };
        });
    }

    /**
     * Add request to queue
     */
    async function enqueue(url, method, headers, body, metadata = {}) {
        try {
            const database = await initDB();

            return new Promise((resolve, reject) => {
                const transaction = database.transaction([STORE_NAME], 'readwrite');
                const store = transaction.objectStore(STORE_NAME);

                const request = {
                    url: url,
                    method: method,
                    headers: headers || {},
                    body: body,
                    metadata: metadata,
                    timestamp: Date.now(),
                    status: 'pending',
                    retries: 0,
                    lastAttempt: null,
                    error: null
                };

                const addRequest = store.add(request);

                addRequest.onsuccess = () => {
                    log('Request queued:', request.method, request.url);
                    resolve(addRequest.result);

                    // Dispatch event for UI updates
                    window.dispatchEvent(new CustomEvent('offline-queue-updated', {
                        detail: { action: 'added', id: addRequest.result }
                    }));

                    // Try to sync immediately if online
                    if (navigator.onLine) {
                        processQueue();
                    }
                };

                addRequest.onerror = () => {
                    const error = addRequest.error;
                    console.error('[Queue Manager] Failed to queue request:', error);

                    // Provide helpful error messages
                    if (error.name === 'QuotaExceededError') {
                        error.message = 'Storage quota exceeded. Cannot queue more requests.';
                    }

                    reject(error);
                };

                transaction.onerror = () => {
                    console.error('[Queue Manager] Transaction failed:', transaction.error);
                    reject(transaction.error);
                };
            });
        } catch (error) {
            console.error('[Queue Manager] Failed to initialize database:', error);
            throw error;
        }
    }

    /**
     * Get all pending requests
     */
    async function getPendingRequests() {
        const database = await initDB();

        return new Promise((resolve, reject) => {
            const transaction = database.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const index = store.index('status');
            const request = index.getAll('pending');

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Update request status
     */
    async function updateRequest(id, updates) {
        const database = await initDB();

        return new Promise((resolve, reject) => {
            const transaction = database.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const getRequest = store.get(id);

            getRequest.onsuccess = () => {
                const request = getRequest.result;
                if (!request) {
                    reject(new Error('Request not found'));
                    return;
                }

                Object.assign(request, updates);
                const updateRequest = store.put(request);

                updateRequest.onsuccess = () => {
                    log('Request updated:', id, updates);
                    resolve(request);

                    window.dispatchEvent(new CustomEvent('offline-queue-updated', {
                        detail: { action: 'updated', id, request }
                    }));
                };

                updateRequest.onerror = () => reject(updateRequest.error);
            };

            getRequest.onerror = () => reject(getRequest.error);
        });
    }

    /**
     * Remove request from queue
     */
    async function dequeue(id) {
        const database = await initDB();

        return new Promise((resolve, reject) => {
            const transaction = database.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const request = store.delete(id);

            request.onsuccess = () => {
                log('Request dequeued:', id);
                resolve();

                window.dispatchEvent(new CustomEvent('offline-queue-updated', {
                    detail: { action: 'removed', id }
                }));
            };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Calculate delay for exponential backoff
     */
    function getBackoffDelay(retries, baseDelay = 1000, maxDelay = 60000) {
        const delay = Math.min(baseDelay * Math.pow(2, retries), maxDelay);
        // Add jitter to prevent thundering herd
        return delay + Math.random() * 1000;
    }

    /**
     * Process a single queued request
     */
    async function processRequest(request) {
        try {
            log('Processing request:', request.method, request.url);

            // Check if enough time has passed since last attempt (backoff)
            if (request.lastAttempt) {
                const backoffDelay = getBackoffDelay(request.retries);
                const timeSinceLastAttempt = Date.now() - request.lastAttempt;

                if (timeSinceLastAttempt < backoffDelay) {
                    log('Skipping request (backoff):', request.url);
                    return false;
                }
            }

            // Prepare fetch options
            const fetchOptions = {
                method: request.method,
                headers: request.headers,
            };

            if (request.body && request.method !== 'GET' && request.method !== 'HEAD') {
                fetchOptions.body = request.body;
            }

            // Update attempt info
            await updateRequest(request.id, {
                lastAttempt: Date.now(),
                retries: request.retries + 1
            });

            // Make the request
            const response = await fetch(request.url, fetchOptions);

            if (response.ok) {
                // Success! Remove from queue
                await dequeue(request.id);
                log('Request succeeded:', request.url);

                // Notify success
                window.dispatchEvent(new CustomEvent('offline-request-synced', {
                    detail: { request, response }
                }));

                return true;
            } else {
                // Server error, keep in queue
                await updateRequest(request.id, {
                    status: 'pending',
                    error: `HTTP ${response.status}: ${response.statusText}`
                });

                log('Request failed (HTTP error):', request.url, response.status);
                return false;
            }
        } catch (error) {
            // Network error or other issue
            log('Request failed (error):', request.url, error);

            // Check max retries
            const maxRetries = 5;
            if (request.retries >= maxRetries) {
                await updateRequest(request.id, {
                    status: 'failed',
                    error: error.message
                });

                window.dispatchEvent(new CustomEvent('offline-request-failed', {
                    detail: { request, error }
                }));

                log('Request permanently failed:', request.url);
            }

            return false;
        }
    }

    /**
     * Process all pending requests in queue
     */
    async function processQueue() {
        if (!navigator.onLine) {
            log('Cannot process queue - offline');
            return;
        }

        try {
            const pending = await getPendingRequests();
            log('Processing queue:', pending.length, 'requests');

            if (pending.length === 0) {
                return;
            }

            // Process requests sequentially
            for (const request of pending) {
                await processRequest(request);

                // Small delay between requests
                await new Promise(resolve => setTimeout(resolve, 100));
            }

            // Check if queue is empty now
            const remaining = await getPendingRequests();
            if (remaining.length === 0) {
                window.dispatchEvent(new Event('offline-queue-empty'));
                log('Queue processed successfully');
            }
        } catch (error) {
            console.error('[Queue Manager] Failed to process queue:', error);
        }
    }

    /**
     * Get queue statistics
     */
    async function getQueueStats() {
        const database = await initDB();

        return new Promise((resolve, reject) => {
            const transaction = database.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const countRequest = store.count();

            countRequest.onsuccess = () => {
                const total = countRequest.result;

                // Get counts by status
                const statusIndex = store.index('status');
                const pendingRequest = statusIndex.count('pending');
                const failedRequest = statusIndex.count('failed');

                Promise.all([
                    new Promise(res => { pendingRequest.onsuccess = () => res(pendingRequest.result); }),
                    new Promise(res => { failedRequest.onsuccess = () => res(failedRequest.result); })
                ]).then(([pending, failed]) => {
                    resolve({ total, pending, failed });
                });
            };

            countRequest.onerror = () => reject(countRequest.error);
        });
    }

    /**
     * Clear all failed requests
     */
    async function clearFailed() {
        const database = await initDB();

        return new Promise((resolve, reject) => {
            const transaction = database.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            const index = store.index('status');
            const request = index.openCursor('failed');

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    store.delete(cursor.primaryKey);
                    cursor.continue();
                } else {
                    resolve();
                }
            };

            request.onerror = () => reject(request.error);
        });
    }

    // Initialize on load
    initDB().catch(error => {
        console.error('[Queue Manager] Initialization failed:', error);
    });

    // Process queue when coming back online
    window.addEventListener('online', () => {
        log('Connection restored, processing queue');
        processQueue();
    });

    // Periodic processing (every 30 seconds)
    setInterval(() => {
        if (navigator.onLine) {
            processQueue();
        }
    }, 30000);

    // Export API
    window.OfflineQueue = {
        enqueue,
        getPendingRequests,
        processQueue,
        getQueueStats,
        clearFailed,
        initDB
    };

    log('Queue manager loaded');
})();
