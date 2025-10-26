/**
 * AJAX Communication Handler
 * Provides utility functions for making API calls with loading states and error handling
 */

const Ajax = {
    /**
     * Base URL for API calls
     */
    baseUrl: window.location.origin,

    /**
     * Default headers for requests
     */
    defaultHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },

    /**
     * Get CSRF token from meta tag or cookie
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        // Fallback to cookie
        const match = document.cookie.match(/csrf_token=([^;]+)/);
        return match ? match[1] : '';
    },

    /**
     * Show loading indicator
     */
    showLoading(target = null) {
        if (target) {
            target.classList.add('loading');
            target.disabled = true;
        }
        // Show global loader if exists
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.style.display = 'flex';
        }
    },

    /**
     * Hide loading indicator
     */
    hideLoading(target = null) {
        if (target) {
            target.classList.remove('loading');
            target.disabled = false;
        }
        // Hide global loader
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.style.display = 'none';
        }
    },

    /**
     * Display error message
     */
    showError(message, container = null) {
        if (container) {
            container.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        } else {
            // Show toast or global error
            this.showToast(message, 'error');
        }
    },

    /**
     * Display success message
     */
    showSuccess(message, container = null) {
        if (container) {
            container.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        } else {
            this.showToast(message, 'success');
        }
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
        const toastId = 'toast-' + Date.now();
        const bgClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';

        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white ${bgClass} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    },

    /**
     * Create toast container if not exists
     */
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    },

    /**
     * Make GET request
     */
    async get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    },

    /**
     * Make POST request
     */
    async post(url, data, options = {}) {
        return this.request(url, { ...options, method: 'POST', body: data });
    },

    /**
     * Make PUT request
     */
    async put(url, data, options = {}) {
        return this.request(url, { ...options, method: 'PUT', body: data });
    },

    /**
     * Make DELETE request
     */
    async delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    },

    /**
     * Make PATCH request
     */
    async patch(url, data, options = {}) {
        return this.request(url, { ...options, method: 'PATCH', body: data });
    },

    /**
     * Main request method
     */
    async request(url, options = {}) {
        const {
            method = 'GET',
            body = null,
            headers = {},
            showLoader = true,
            loaderTarget = null,
            errorContainer = null,
            successContainer = null,
            onSuccess = null,
            onError = null
        } = options;

        // Prepare headers
        const requestHeaders = {
            ...this.defaultHeaders,
            ...headers
        };

        // Add CSRF token for non-GET requests
        if (method !== 'GET') {
            requestHeaders['X-CSRF-Token'] = this.getCsrfToken();
        }

        // Prepare request config
        const config = {
            method,
            headers: requestHeaders,
            credentials: 'same-origin'
        };

        // Add body for non-GET requests
        if (body && method !== 'GET') {
            if (body instanceof FormData) {
                delete config.headers['Content-Type']; // Let browser set it
                config.body = body;
            } else {
                config.body = JSON.stringify(body);
            }
        }

        // Show loading
        if (showLoader) {
            this.showLoading(loaderTarget);
        }

        try {
            const response = await fetch(this.baseUrl + url, config);
            const contentType = response.headers.get('content-type');
            let data;

            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = await response.text();
            }

            // Hide loading
            if (showLoader) {
                this.hideLoading(loaderTarget);
            }

            if (!response.ok) {
                const errorMessage = data.message || data.error || `HTTP error! status: ${response.status}`;
                this.showError(errorMessage, errorContainer);
                
                if (onError) {
                    onError(data, response);
                }
                
                throw new Error(errorMessage);
            }

            // Show success message if provided
            if (data.message && successContainer) {
                this.showSuccess(data.message, successContainer);
            }

            // Call success callback
            if (onSuccess) {
                onSuccess(data, response);
            }

            return data;

        } catch (error) {
            // Hide loading
            if (showLoader) {
                this.hideLoading(loaderTarget);
            }

            // Network or other errors
            if (!error.message.includes('HTTP error')) {
                this.showError('Network error. Please check your connection.', errorContainer);
            }

            if (onError && !error.message.includes('HTTP error')) {
                onError(error, null);
            }

            throw error;
        }
    },

    /**
     * Submit form via AJAX
     */
    async submitForm(formElement, options = {}) {
        const formData = new FormData(formElement);
        const method = formElement.method.toUpperCase() || 'POST';
        const action = formElement.action || window.location.href;

        return this.request(action, {
            method,
            body: formData,
            loaderTarget: formElement.querySelector('button[type="submit"]'),
            ...options
        });
    },

    /**
     * Load content into element
     */
    async loadContent(url, targetElement, options = {}) {
        try {
            const data = await this.get(url, {
                ...options,
                headers: { ...options.headers, 'Accept': 'text/html' }
            });

            if (typeof targetElement === 'string') {
                targetElement = document.querySelector(targetElement);
            }

            if (targetElement) {
                targetElement.innerHTML = data;
            }

            return data;
        } catch (error) {
            console.error('Failed to load content:', error);
            throw error;
        }
    },

    /**
     * Upload file with progress
     */
    uploadFile(url, file, options = {}) {
        return new Promise((resolve, reject) => {
            const {
                onProgress = null,
                onSuccess = null,
                onError = null,
                additionalData = {}
            } = options;

            const formData = new FormData();
            formData.append('file', file);

            // Add additional data
            Object.keys(additionalData).forEach(key => {
                formData.append(key, additionalData[key]);
            });

            const xhr = new XMLHttpRequest();

            // Progress handler
            if (onProgress) {
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        onProgress(percentComplete, e);
                    }
                });
            }

            // Success handler
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    if (onSuccess) onSuccess(response);
                    resolve(response);
                } else {
                    const error = new Error(`Upload failed: ${xhr.status}`);
                    if (onError) onError(error);
                    reject(error);
                }
            });

            // Error handler
            xhr.addEventListener('error', () => {
                const error = new Error('Upload failed');
                if (onError) onError(error);
                reject(error);
            });

            // Send request
            xhr.open('POST', this.baseUrl + url);
            xhr.setRequestHeader('X-CSRF-Token', this.getCsrfToken());
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Auto-submit forms with data-ajax attribute
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const errorContainer = form.querySelector('.form-errors');
            const successContainer = form.querySelector('.form-success');
            
            try {
                const response = await Ajax.submitForm(form, {
                    errorContainer,
                    successContainer,
                    onSuccess: (data) => {
                        // Reset form on success
                        if (form.dataset.resetOnSuccess !== 'false') {
                            form.reset();
                        }
                        
                        // Redirect if specified
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    }
                });
            } catch (error) {
                // Error already handled by Ajax.request
            }
        });
    });

    // Auto-load content for elements with data-load attribute
    document.querySelectorAll('[data-load]').forEach(element => {
        const url = element.dataset.load;
        if (url) {
            Ajax.loadContent(url, element);
        }
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Ajax;
}
