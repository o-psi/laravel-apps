/**
 * Laravel Offline - Sync Status UI Component
 *
 * Displays realtime sync status and pending requests
 */

(function() {
    'use strict';

    const DEBUG = false;

    function log(...args) {
        if (DEBUG) {
            console.log('[Sync Status]', ...args);
        }
    }

    /**
     * Create and inject sync status widget
     */
    function createSyncStatusWidget() {
        const widget = document.createElement('div');
        widget.id = 'offline-sync-status';
        widget.className = 'offline-sync-status';
        widget.innerHTML = `
            <div class="sync-status-header">
                <span class="sync-status-icon">⏳</span>
                <span class="sync-status-text">Syncing...</span>
                <span class="sync-status-count">0</span>
            </div>
            <div class="sync-status-details"></div>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .offline-sync-status {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                padding: 12px 16px;
                font-family: system-ui, -apple-system, sans-serif;
                font-size: 14px;
                z-index: 9998;
                display: none;
                min-width: 250px;
                max-width: 350px;
            }

            .offline-sync-status.visible {
                display: block;
            }

            .sync-status-header {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .sync-status-icon {
                font-size: 18px;
                animation: spin 2s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .sync-status-text {
                flex: 1;
                font-weight: 500;
            }

            .sync-status-count {
                background: #f59e0b;
                color: white;
                border-radius: 12px;
                padding: 2px 8px;
                font-size: 12px;
                font-weight: 600;
            }

            .sync-status-details {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #e5e7eb;
                display: none;
            }

            .sync-status-details.visible {
                display: block;
            }

            .sync-status-item {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                font-size: 12px;
                color: #6b7280;
            }

            .sync-status-success {
                background: #10b981;
                color: white;
            }

            .sync-status-success .sync-status-icon {
                animation: none;
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(widget);

        return widget;
    }

    /**
     * Update widget with current queue stats
     */
    async function updateWidget(widget) {
        if (!window.OfflineQueue) {
            return;
        }

        try {
            const stats = await window.OfflineQueue.getQueueStats();

            if (stats.pending > 0) {
                widget.classList.add('visible');
                widget.querySelector('.sync-status-count').textContent = stats.pending;
                widget.querySelector('.sync-status-text').textContent =
                    `${stats.pending} ${stats.pending === 1 ? 'request' : 'requests'} pending`;
            } else {
                // Show success briefly, then hide
                if (widget.dataset.hadPending === 'true') {
                    showSuccess(widget);
                    setTimeout(() => {
                        widget.classList.remove('visible');
                        widget.dataset.hadPending = 'false';
                    }, 3000);
                }
            }

            if (stats.pending > 0) {
                widget.dataset.hadPending = 'true';
            }
        } catch (error) {
            log('Failed to update widget:', error);
        }
    }

    /**
     * Show success state
     */
    function showSuccess(widget) {
        widget.classList.add('sync-status-success');
        widget.querySelector('.sync-status-icon').textContent = '✓';
        widget.querySelector('.sync-status-text').textContent = 'All synced!';
        widget.querySelector('.sync-status-count').style.display = 'none';

        setTimeout(() => {
            widget.classList.remove('sync-status-success');
            widget.querySelector('.sync-status-icon').textContent = '⏳';
            widget.querySelector('.sync-status-count').style.display = '';
        }, 3000);
    }

    /**
     * Initialize sync status widget
     */
    function init() {
        // Only initialize if queue manager is available
        if (!window.OfflineQueue) {
            log('Queue manager not available, skipping');
            return;
        }

        const widget = createSyncStatusWidget();

        // Initial update
        updateWidget(widget);

        // Listen for queue updates
        window.addEventListener('offline-queue-updated', () => {
            updateWidget(widget);
        });

        // Listen for sync completion from service worker
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'SYNC_COMPLETE') {
                    log('Sync complete:', event.data.results);
                    updateWidget(widget);
                }
            });
        }

        // Periodic updates
        setInterval(() => {
            updateWidget(widget);
        }, 5000);

        // Update on online/offline
        window.addEventListener('online', () => {
            updateWidget(widget);
        });

        window.addEventListener('offline', () => {
            updateWidget(widget);
        });

        log('Sync status widget initialized');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
