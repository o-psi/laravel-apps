/**
 * Laravel Offline - Form Persistence Module
 *
 * Automatically saves form data to localStorage and restores it on page load.
 * Prevents data loss from crashes, accidental navigation, or connection issues.
 *
 * Usage:
 *   <form data-persist="unique-form-id">
 *     <!-- form fields -->
 *   </form>
 */

(function() {
    'use strict';

    const STORAGE_PREFIX = 'offline-form-';
    const SAVE_DEBOUNCE = 500; // ms
    const DEBUG = false;

    function log(...args) {
        if (DEBUG) {
            console.log('[Form Persistence]', ...args);
        }
    }

    /**
     * Get storage key for a form
     */
    function getStorageKey(formId) {
        return STORAGE_PREFIX + formId;
    }

    /**
     * Save form data to localStorage
     */
    function saveFormData(form, formId) {
        const formData = {};
        const elements = form.elements;

        for (let i = 0; i < elements.length; i++) {
            const element = elements[i];

            if (!element.name) continue;
            if (element.type === 'password') continue; // Never save passwords
            if (element.type === 'submit' || element.type === 'button') continue;

            if (element.type === 'checkbox') {
                formData[element.name] = element.checked;
            } else if (element.type === 'radio') {
                if (element.checked) {
                    formData[element.name] = element.value;
                }
            } else if (element.tagName === 'SELECT' && element.multiple) {
                const selected = [];
                for (let j = 0; j < element.options.length; j++) {
                    if (element.options[j].selected) {
                        selected.push(element.options[j].value);
                    }
                }
                formData[element.name] = selected;
            } else {
                formData[element.name] = element.value;
            }
        }

        try {
            localStorage.setItem(getStorageKey(formId), JSON.stringify({
                data: formData,
                timestamp: Date.now(),
                url: window.location.href
            }));
            log('Saved form data:', formId);
        } catch (e) {
            console.warn('[Form Persistence] Failed to save:', e);
        }
    }

    /**
     * Restore form data from localStorage
     */
    function restoreFormData(form, formId) {
        try {
            const stored = localStorage.getItem(getStorageKey(formId));
            if (!stored) return false;

            const { data, timestamp, url } = JSON.parse(stored);

            // Check if data is from the same URL
            if (url !== window.location.href) {
                log('Stored data is from different URL, skipping restore');
                return false;
            }

            // Check if data is not too old (24 hours)
            const age = Date.now() - timestamp;
            if (age > 86400000) {
                log('Stored data is too old, clearing');
                clearFormData(formId);
                return false;
            }

            // Restore values
            const elements = form.elements;
            let restoredCount = 0;

            for (let i = 0; i < elements.length; i++) {
                const element = elements[i];
                if (!element.name || !(element.name in data)) continue;

                if (element.type === 'checkbox') {
                    element.checked = data[element.name];
                    restoredCount++;
                } else if (element.type === 'radio') {
                    if (element.value === data[element.name]) {
                        element.checked = true;
                        restoredCount++;
                    }
                } else if (element.tagName === 'SELECT' && element.multiple) {
                    const selected = data[element.name] || [];
                    for (let j = 0; j < element.options.length; j++) {
                        element.options[j].selected = selected.includes(element.options[j].value);
                    }
                    restoredCount++;
                } else {
                    element.value = data[element.name];
                    restoredCount++;
                }
            }

            if (restoredCount > 0) {
                log('Restored', restoredCount, 'fields for form:', formId);
                showRestoreNotification(form, formId);
                return true;
            }

            return false;
        } catch (e) {
            console.warn('[Form Persistence] Failed to restore:', e);
            return false;
        }
    }

    /**
     * Clear stored form data
     */
    function clearFormData(formId) {
        try {
            localStorage.removeItem(getStorageKey(formId));
            log('Cleared form data:', formId);
        } catch (e) {
            console.warn('[Form Persistence] Failed to clear:', e);
        }
    }

    /**
     * Show notification that data was restored
     */
    function showRestoreNotification(form, formId) {
        const notification = document.createElement('div');
        notification.className = 'offline-form-restored-notice';
        notification.innerHTML = `
            <span>✓ Form data restored</span>
            <button type="button" class="offline-form-clear-btn">Clear</button>
        `;

        notification.style.cssText = `
            position: sticky;
            top: 0;
            background: #10b981;
            color: white;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            z-index: 10;
        `;

        const clearBtn = notification.querySelector('.offline-form-clear-btn');
        clearBtn.style.cssText = `
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
        `;

        clearBtn.addEventListener('click', function() {
            clearFormData(formId);
            form.reset();
            notification.remove();
        });

        form.insertBefore(notification, form.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    /**
     * Initialize form persistence for a single form
     */
    function initializeForm(form) {
        const formId = form.getAttribute('data-persist');
        if (!formId) return;

        log('Initializing form:', formId);

        // Restore saved data
        restoreFormData(form, formId);

        // Set up auto-save
        let saveTimeout;
        const debouncedSave = function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => saveFormData(form, formId), SAVE_DEBOUNCE);
        };

        form.addEventListener('input', debouncedSave);
        form.addEventListener('change', debouncedSave);

        // Clear data on successful submit
        form.addEventListener('submit', function() {
            log('Form submitted, clearing saved data');
            clearFormData(formId);
        });
    }

    /**
     * Initialize all forms with data-persist attribute
     */
    function initializeAllForms() {
        const forms = document.querySelectorAll('form[data-persist]');
        log('Found', forms.length, 'persistent forms');

        forms.forEach(initializeForm);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAllForms);
    } else {
        initializeAllForms();
    }

    // Also watch for dynamically added forms (e.g., in SPAs)
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.matches && node.matches('form[data-persist]')) {
                            initializeForm(node);
                        }
                        // Check children too
                        if (node.querySelectorAll) {
                            const forms = node.querySelectorAll('form[data-persist]');
                            forms.forEach(initializeForm);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Export for manual initialization if needed
    window.OfflineFormPersistence = {
        initialize: initializeAllForms,
        initializeForm: initializeForm,
        clear: clearFormData
    };

    log('Form persistence module loaded');
})();
