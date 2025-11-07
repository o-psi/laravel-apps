/**
 * Laravel Offline - Global Request Interceptor
 *
 * Intercepts ALL outgoing HTTP requests (fetch, XMLHttpRequest, links, forms)
 * and queues them when offline. Works with any HTML content, not just forms.
 *
 * Usage: Simply include this script - it works automatically!
 */

(function() {
    'use strict';

    const DEBUG = false;

    function log(...args) {
        if (DEBUG) {
            console.log('[Offline Interceptor]', ...args);
        }
    }

    /**
     * User-configurable filter function
     * Return false to skip queueing a request
     */
    window.OfflineInterceptor = window.OfflineInterceptor || {};
    window.OfflineInterceptor.shouldQueue = window.OfflineInterceptor.shouldQueue || function(method, url, headers) {
        return true; // Default: queue everything
    };

    /**
     * Check if request should be queued
     */
    function shouldQueueRequest(method, url, headers) {
        // Only queue mutation methods
        const queueableMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        if (!queueableMethods.includes(method.toUpperCase())) {
            return false;
        }

        // Don't queue if we're online
        if (navigator.onLine) {
            return false;
        }

        // Don't queue external URLs
        try {
            const requestUrl = new URL(url, window.location.href);
            if (requestUrl.origin !== window.location.origin) {
                return false;
            }
        } catch (e) {
            return false;
        }

        // User-configurable filter
        return window.OfflineInterceptor.shouldQueue(method, url, headers);
    }

    /**
     * Get CSRF token from page
     */
    function getCSRFToken() {
        // Try meta tag first
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.content;
        }

        // Try hidden input
        const inputToken = document.querySelector('input[name="_token"]');
        if (inputToken) {
            return inputToken.value;
        }

        return null;
    }

    /**
     * Show user notification
     */
    function showQueuedNotification(message = 'Request saved and will sync when online') {
        const notification = document.createElement('div');
        notification.className = 'offline-queued-notification';
        notification.innerHTML = `<span>✓ ${message}</span>`;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            z-index: 10000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideInRight 0.3s ease-out;
        `;

        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        if (!document.querySelector('#offline-interceptor-styles')) {
            style.id = 'offline-interceptor-styles';
            document.head.appendChild(style);
        }

        document.body.appendChild(notification);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Intercept Fetch API
     */
    if (window.fetch) {
        const originalFetch = window.fetch;

        window.fetch = function(resource, options = {}) {
            const url = typeof resource === 'string' ? resource : resource.url;
            const method = options.method || 'GET';

            if (shouldQueueRequest(method, url, options.headers)) {
                log('Intercepted fetch:', method, url);

                // Queue the request
                return window.OfflineQueue.enqueue(
                    url,
                    method,
                    options.headers || {},
                    options.body,
                    { type: 'fetch-api', intercepted: true }
                ).then(() => {
                    showQueuedNotification();

                    // Return a resolved promise to satisfy the caller
                    return new Response(JSON.stringify({ queued: true, offline: true }), {
                        status: 202,
                        statusText: 'Accepted (Queued)',
                        headers: { 'Content-Type': 'application/json' }
                    });
                }).catch(error => {
                    console.error('[Offline Interceptor] Failed to queue fetch:', error);
                    return Promise.reject(error);
                });
            }

            // Otherwise, proceed with normal fetch
            return originalFetch.apply(this, arguments);
        };

        log('Fetch API interceptor installed');
    }

    /**
     * Intercept XMLHttpRequest
     */
    if (window.XMLHttpRequest) {
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        const originalXHRSetRequestHeader = XMLHttpRequest.prototype.setRequestHeader;

        XMLHttpRequest.prototype.open = function(method, url, ...args) {
            this._method = method;
            this._url = url;
            this._headers = {};
            return originalXHROpen.apply(this, [method, url, ...args]);
        };

        XMLHttpRequest.prototype.setRequestHeader = function(header, value) {
            if (!this._headers) this._headers = {};
            this._headers[header] = value;
            return originalXHRSetRequestHeader.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function(body) {
            if (shouldQueueRequest(this._method, this._url, this._headers)) {
                log('Intercepted XMLHttpRequest:', this._method, this._url);

                // Get headers
                const headers = {};
                if (this._headers) {
                    Object.assign(headers, this._headers);
                }

                // Queue the request
                window.OfflineQueue.enqueue(
                    this._url,
                    this._method,
                    headers,
                    body,
                    { type: 'xmlhttprequest', intercepted: true }
                ).then(() => {
                    showQueuedNotification();

                    // Trigger success handlers
                    this.status = 202;
                    this.statusText = 'Accepted (Queued)';
                    this.responseText = JSON.stringify({ queued: true, offline: true });
                    this.response = this.responseText;

                    if (this.onload) {
                        this.onload({ target: this });
                    }

                    if (this.onreadystatechange) {
                        this.readyState = 4;
                        this.onreadystatechange({ target: this });
                    }
                }).catch(error => {
                    console.error('[Offline Interceptor] Failed to queue XHR:', error);
                    if (this.onerror) {
                        this.onerror({ target: this });
                    }
                });

                return;
            }

            // Otherwise, proceed with normal send
            return originalXHRSend.apply(this, arguments);
        };

        log('XMLHttpRequest interceptor installed');
    }

    /**
     * Intercept form submissions
     */
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        const method = (form.method || 'GET').toUpperCase();
        const action = form.action || window.location.href;

        if (shouldQueueRequest(method, action)) {
            e.preventDefault();
            log('Intercepted form submission:', method, action);

            // Get form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            // Prepare headers
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            // Add CSRF token
            const csrfToken = getCSRFToken();
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            // Queue the request
            window.OfflineQueue.enqueue(
                action,
                method,
                headers,
                JSON.stringify(data),
                { type: 'form-submission', intercepted: true, formId: form.id || form.name }
            ).then(() => {
                showQueuedNotification('Form saved and will sync when online');

                // Reset form if successful
                form.reset();

                // Trigger custom event
                form.dispatchEvent(new CustomEvent('offline-queued', {
                    detail: { method, action, data }
                }));
            }).catch(error => {
                console.error('[Offline Interceptor] Failed to queue form:', error);
                alert('Failed to save form offline. Please try again.');
            });
        }
    }, true);  // Use capture phase to intercept before other handlers

    /**
     * Intercept link clicks (for DELETE requests via data attributes)
     */
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[data-method], button[data-method]');
        if (!link) return;

        const method = link.getAttribute('data-method');
        const url = link.getAttribute('href') || link.getAttribute('data-url');

        if (shouldQueueRequest(method, url)) {
            e.preventDefault();
            log('Intercepted link with method:', method, url);

            // Get CSRF token
            const csrfToken = getCSRFToken();
            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            };

            // Queue the request
            window.OfflineQueue.enqueue(
                url,
                method.toUpperCase(),
                headers,
                null,
                { type: 'link-method', intercepted: true }
            ).then(() => {
                showQueuedNotification();
            }).catch(error => {
                console.error('[Offline Interceptor] Failed to queue link:', error);
                alert('Failed to save action offline. Please try again.');
            });
        }
    }, true);

    log('Global offline interceptor initialized');
})();
