/**
 * Project Views - JavaScript Functionality
 * Handles interactions for project management views
 */

(function() {
    'use strict';

    // ========================================
    // Global Search Functionality
    // ========================================
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        let searchTimeout;
        globalSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value.trim();
                if (query.length >= 3) {
                    performGlobalSearch(query);
                }
            }, 300);
        });
    }

    function performGlobalSearch(query) {
        // Implementation for global search across projects and orders
        console.log('Searching for:', query);
        // AJAX call to search endpoint
    }

    // ========================================
    // Filter Form Handling
    // ========================================
    const filterForms = document.querySelectorAll('.enterprise-filters__form');
    filterForms.forEach(form => {
        // Auto-submit on select change
        const selects = form.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', () => {
                // Optional: auto-submit form on filter change
                // form.submit();
            });
        });
    });

    // ========================================
    // Data Grid Interactions
    // ========================================
    class DataGrid {
        constructor(tableElement) {
            this.table = tableElement;
            this.init();
        }

        init() {
            this.setupSorting();
            this.setupRowSelection();
            this.setupInlineEditing();
        }

        setupSorting() {
            const headers = this.table.querySelectorAll('th[data-sortable="true"]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const column = header.dataset.column;
                    const currentSort = header.dataset.sort || 'none';
                    const newSort = currentSort === 'asc' ? 'desc' : 'asc';
                    
                    // Update sort indicators
                    headers.forEach(h => h.dataset.sort = 'none');
                    header.dataset.sort = newSort;
                    
                    this.sortTable(column, newSort);
                });
            });
        }

        sortTable(column, direction) {
            // Table sorting logic
            const tbody = this.table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aVal = a.querySelector(`[data-column="${column}"]`)?.textContent || '';
                const bVal = b.querySelector(`[data-column="${column}"]`)?.textContent || '';
                
                if (direction === 'asc') {
                    return aVal.localeCompare(bVal);
                } else {
                    return bVal.localeCompare(aVal);
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }

        setupRowSelection() {
            const checkboxes = this.table.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    this.updateSelectedCount();
                });
            });
        }

        setupInlineEditing() {
            const editableCells = this.table.querySelectorAll('[data-editable="true"]');
            editableCells.forEach(cell => {
                cell.addEventListener('dblclick', () => {
                    this.makeEditable(cell);
                });
            });
        }

        makeEditable(cell) {
            const currentValue = cell.textContent;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'enterprise-input';
            
            cell.textContent = '';
            cell.appendChild(input);
            input.focus();
            
            input.addEventListener('blur', () => {
                this.saveEdit(cell, input.value);
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    this.saveEdit(cell, input.value);
                } else if (e.key === 'Escape') {
                    cell.textContent = currentValue;
                }
            });
        }

        saveEdit(cell, newValue) {
            const row = cell.closest('tr');
            const id = row.dataset.id;
            const column = cell.dataset.column;
            
            // AJAX call to save
            fetch(`/api/update/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    column: column,
                    value: newValue
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cell.textContent = newValue;
                    showToast('Updated successfully', 'success');
                } else {
                    showToast('Update failed', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Update failed', 'error');
            });
        }

        updateSelectedCount() {
            const selected = this.table.querySelectorAll('input[type="checkbox"]:checked');
            console.log(`${selected.length} rows selected`);
        }
    }

    // Initialize data grids
    document.querySelectorAll('.enterprise-grid__table').forEach(table => {
        new DataGrid(table);
    });

    // ========================================
    // Modal Handling
    // ========================================
    class Modal {
        constructor(modalId) {
            this.modal = document.getElementById(modalId);
            if (this.modal) {
                this.init();
            }
        }

        init() {
            const closeBtn = this.modal.querySelector('.enterprise-modal__close');
            const overlay = this.modal.querySelector('.enterprise-modal__overlay');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }
            
            if (overlay) {
                overlay.addEventListener('click', () => this.close());
            }
            
            // Escape key to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen()) {
                    this.close();
                }
            });
        }

        open() {
            this.modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        close() {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        isOpen() {
            return this.modal.style.display === 'flex';
        }
    }

    // ========================================
    // Toast Notifications
    // ========================================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `enterprise-toast enterprise-toast--${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.classList.add('enterprise-toast--show');
        }, 10);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            toast.classList.remove('enterprise-toast--show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }

    // ========================================
    // Tab Navigation
    // ========================================
    const tabLinks = document.querySelectorAll('.enterprise-tabs__link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // If using hash navigation, let it proceed
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetPane = document.getElementById(targetId);
                
                // Hide all panes
                document.querySelectorAll('.enterprise-tab-pane').forEach(pane => {
                    pane.style.display = 'none';
                });
                
                // Show target pane
                if (targetPane) {
                    targetPane.style.display = 'block';
                }
                
                // Update active tab
                document.querySelectorAll('.enterprise-tabs__item').forEach(item => {
                    item.classList.remove('enterprise-tabs__item--active');
                });
                this.closest('.enterprise-tabs__item').classList.add('enterprise-tabs__item--active');
            }
        });
    });

    // ========================================
    // Progress Bar Animations
    // ========================================
    const progressBars = document.querySelectorAll('.enterprise-progress__bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
            bar.style.transition = 'width 1s ease-in-out';
        }, 100);
    });

    // ========================================
    // Keyboard Shortcuts
    // ========================================
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for global search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            globalSearch?.focus();
        }
        
        // Ctrl/Cmd + N for new project (on projects page)
        if ((e.ctrlKey || e.metaKey) && e.key === 'n' && window.location.pathname === '/projects') {
            e.preventDefault();
            window.location.href = '/projects/create';
        }
    });

    // ========================================
    // Real-time Updates (WebSocket or Polling)
    // ========================================
    let activityUpdateInterval;
    
    function startActivityUpdates() {
        if (document.querySelector('.activity-stream')) {
            activityUpdateInterval = setInterval(() => {
                updateActivityStream();
            }, 30000); // Update every 30 seconds
        }
    }

    function updateActivityStream() {
        // Fetch latest activities
        fetch('/api/activities/recent')
            .then(response => response.json())
            .then(data => {
                // Update activity stream with new data
                console.log('Activity stream updated', data);
            })
            .catch(error => {
                console.error('Failed to update activity stream:', error);
            });
    }

    // Start activity updates if on relevant page
    startActivityUpdates();

    // ========================================
    // Export Functionality
    // ========================================
    function exportToCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(col => {
                let data = col.textContent.trim();
                data = data.replace(/"/g, '""'); // Escape quotes
                return `"${data}"`;
            });
            csv.push(rowData.join(','));
        });
        
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }

    // ========================================
    // Form Validation
    // ========================================
    const forms = document.querySelectorAll('form[data-validate="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('enterprise-input--error');
                showFieldError(input, 'This field is required');
            } else {
                input.classList.remove('enterprise-input--error');
                hideFieldError(input);
            }
        });
        
        return isValid;
    }

    function showFieldError(input, message) {
        let errorEl = input.nextElementSibling;
        if (!errorEl || !errorEl.classList.contains('field-error')) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error';
            input.parentNode.insertBefore(errorEl, input.nextSibling);
        }
        errorEl.textContent = message;
    }

    function hideFieldError(input) {
        const errorEl = input.nextElementSibling;
        if (errorEl && errorEl.classList.contains('field-error')) {
            errorEl.remove();
        }
    }

    // ========================================
    // Expose utility functions globally
    // ========================================
    window.ProjectViews = {
        showToast,
        exportToCSV,
        Modal
    };

})();
