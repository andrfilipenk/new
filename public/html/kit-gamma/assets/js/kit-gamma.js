/**
 * Kit Gamma: Analytical Depth
 * JavaScript for data exploration, filtering, and visualization
 */

(function() {
    'use strict';

    // ============================================
    // Global Search with Filters
    // ============================================
    const initGlobalSearch = () => {
        const searchInput = document.querySelector('.gamma-search input');
        
        if (searchInput) {
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }
    };

    // ============================================
    // Data Table Sorting
    // ============================================
    const initTableSorting = () => {
        const table = document.querySelector('.gamma-data-table');
        if (!table) return;

        const headers = table.querySelectorAll('th[data-sortable], th i.bi-arrow-down-up');
        
        headers.forEach(header => {
            const th = header.tagName === 'TH' ? header : header.closest('th');
            th.style.cursor = 'pointer';
            
            th.addEventListener('click', () => {
                const columnIndex = Array.from(th.parentElement.children).indexOf(th);
                sortTable(table, columnIndex);
            });
        });
    };

    const sortTable = (table, columnIndex) => {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        const sortedRows = rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();
            
            // Check if numeric
            if (!isNaN(aValue) && !isNaN(bValue)) {
                return parseFloat(aValue) - parseFloat(bValue);
            }
            
            return aValue.localeCompare(bValue);
        });
        
        // Re-append sorted rows
        sortedRows.forEach(row => tbody.appendChild(row));
        
        console.log(`Sorted by column ${columnIndex}`);
    };

    // ============================================
    // Advanced Filtering
    // ============================================
    const initFilters = () => {
        const dateFilter = document.querySelector('.gamma-filter-group select');
        const checkboxes = document.querySelectorAll('.gamma-checkbox-group input[type="checkbox"]');
        const clearBtn = document.querySelector('.gamma-sidebar-title .btn-link');
        
        if (dateFilter) {
            dateFilter.addEventListener('change', applyFilters);
        }
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', applyFilters);
        });
        
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                clearAllFilters();
            });
        }
    };

    const applyFilters = () => {
        const dateRange = document.querySelector('.gamma-filter-group select')?.value;
        const selectedDepts = Array.from(
            document.querySelectorAll('.gamma-checkbox-group input:checked')
        ).map(cb => cb.nextSibling.textContent.trim());
        
        console.log('Applying filters:', { dateRange, departments: selectedDepts });
        
        // In production: fetch filtered data from API
        // fetchFilteredData({ dateRange, departments: selectedDepts });
    };

    const clearAllFilters = () => {
        const dateFilter = document.querySelector('.gamma-filter-group select');
        const checkboxes = document.querySelectorAll('.gamma-checkbox-group input[type="checkbox"]');
        
        if (dateFilter) dateFilter.selectedIndex = 0;
        checkboxes.forEach(cb => cb.checked = false);
        
        applyFilters();
    };

    // ============================================
    // Detail Panel Management
    // ============================================
    const initDetailPanel = () => {
        const tableRows = document.querySelectorAll('.gamma-data-table tbody tr');
        const detailPanel = document.getElementById('detailPanel');
        
        tableRows.forEach(row => {
            row.addEventListener('click', (e) => {
                // Don't trigger if clicking checkbox or button
                if (e.target.closest('input, button, a')) return;
                
                showTaskDetails(row, detailPanel);
            });
        });
    };

    const showTaskDetails = (row, panel) => {
        if (!panel) return;
        
        const taskName = row.querySelector('td:nth-child(2) strong').textContent;
        const project = row.querySelector('td:nth-child(2) small').textContent;
        const created = row.querySelector('td:nth-child(3)').textContent;
        const modified = row.querySelector('td:nth-child(4)').textContent;
        const status = row.querySelector('.badge').textContent;
        
        panel.classList.remove('collapsed');
        
        const detailBody = panel.querySelector('.gamma-detail-body');
        detailBody.innerHTML = `
            <div class="mb-3">
                <strong class="d-block mb-1">Task</strong>
                <p>${taskName}</p>
            </div>
            <div class="mb-3">
                <strong class="d-block mb-1">Project</strong>
                <p>${project}</p>
            </div>
            <div class="mb-3">
                <strong class="d-block mb-1">Status</strong>
                <span class="badge bg-info">${status}</span>
            </div>
            <div class="mb-3">
                <strong class="d-block mb-1">Created</strong>
                <code>${created}</code>
            </div>
            <div class="mb-3">
                <strong class="d-block mb-1">Modified</strong>
                <code>${modified}</code>
            </div>
            <hr>
            <h5>Activity</h5>
            <div class="small text-muted">
                <p>Oct 14, 10:30 - Status changed to "In Progress"</p>
                <p>Oct 13, 15:20 - Assigned to Alex Data</p>
                <p>Oct 12, 09:45 - Task created</p>
            </div>
        `;
    };

    // ============================================
    // Saved Views Management
    // ============================================
    const initSavedViews = () => {
        const savedItems = document.querySelectorAll('.gamma-saved-item');
        
        savedItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const viewName = item.querySelector('span').textContent;
                loadSavedView(viewName);
            });
        });
    };

    const loadSavedView = (viewName) => {
        console.log(`Loading saved view: ${viewName}`);
        
        // In production: fetch saved view configuration and apply
        alert(`Loading view: ${viewName}\n\n(In production, this would load the saved dashboard configuration)`);
    };

    // ============================================
    // Subscription Toggle
    // ============================================
    const initSubscriptions = () => {
        const toggles = document.querySelectorAll('.gamma-subscription-item .form-check-input');
        
        toggles.forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                const item = e.target.closest('.gamma-subscription-item');
                const subscriptionName = item.querySelector('strong').textContent;
                const enabled = e.target.checked;
                
                console.log(`Subscription "${subscriptionName}" ${enabled ? 'enabled' : 'disabled'}`);
                
                // In production: update subscription via API
                // updateSubscription(subscriptionId, enabled);
                
                showToast(
                    enabled ? 'Subscription enabled' : 'Subscription disabled',
                    'success'
                );
            });
        });
    };

    // ============================================
    // Batch Selection
    // ============================================
    const initBatchSelection = () => {
        const selectAll = document.querySelector('.gamma-data-table thead input[type="checkbox"]');
        const rowCheckboxes = document.querySelectorAll('.gamma-data-table tbody input[type="checkbox"]');
        
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                rowCheckboxes.forEach(cb => cb.checked = e.target.checked);
                updateBatchToolbar();
            });
        }
        
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBatchToolbar);
        });
    };

    const updateBatchToolbar = () => {
        const checked = document.querySelectorAll('.gamma-data-table tbody input[type="checkbox"]:checked');
        console.log(`${checked.length} rows selected`);
        
        // In production: show/hide batch action toolbar
        if (checked.length > 0) {
            // showBatchToolbar(checked.length);
        }
    };

    // ============================================
    // Export Functionality
    // ============================================
    const initExport = () => {
        const exportBtn = document.querySelector('.gamma-page-header .btn-outline-secondary:first-child');
        
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                console.log('Exporting dashboard data...');
                
                // In production: generate and download export
                alert('Export Options:\n\n• PDF Report\n• Excel Spreadsheet\n• CSV Data\n\n(In production, this would generate the export)');
            });
        }
    };

    // ============================================
    // View Mode Toggle
    // ============================================
    const initViewModeToggle = () => {
        const viewButtons = document.querySelectorAll('.gamma-panel-controls .btn-group button');
        
        viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                viewButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const mode = btn.textContent.trim();
                console.log(`Switching to ${mode} view`);
                
                // In production: switch between table/chart/timeline views
            });
        });
    };

    // ============================================
    // Toast Notifications
    // ============================================
    const showToast = (message, type = 'info') => {
        const container = getToastContainer();
        const bgClass = {
            success: 'bg-success',
            info: 'bg-info',
            warning: 'bg-warning',
            danger: 'bg-danger'
        }[type] || 'bg-info';
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${bgClass} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    };

    const getToastContainer = () => {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }
        return container;
    };

    // ============================================
    // Auto-save Dashboard Layout
    // ============================================
    const initAutoSave = () => {
        // In production: save dashboard layout to localStorage or backend
        window.addEventListener('beforeunload', () => {
            const layout = {
                filters: getActiveFilters(),
                selectedView: getSelectedView(),
                panelStates: getPanelStates()
            };
            
            localStorage.setItem('gamma-dashboard-layout', JSON.stringify(layout));
        });
    };

    const getActiveFilters = () => {
        return {
            dateRange: document.querySelector('.gamma-filter-group select')?.value,
            departments: Array.from(
                document.querySelectorAll('.gamma-checkbox-group input:checked')
            ).map(cb => cb.nextSibling.textContent.trim())
        };
    };

    const getSelectedView = () => {
        const activeBtn = document.querySelector('.gamma-panel-controls .btn.active');
        return activeBtn ? activeBtn.textContent.trim() : 'Table';
    };

    const getPanelStates = () => {
        return {
            rightSidebarCollapsed: document.getElementById('detailPanel')?.classList.contains('collapsed')
        };
    };

    // ============================================
    // Initialize All Features
    // ============================================
    const init = () => {
        console.log('Kit Gamma: Analytical Depth - Initializing...');
        
        initGlobalSearch();
        initTableSorting();
        initFilters();
        initDetailPanel();
        initSavedViews();
        initSubscriptions();
        initBatchSelection();
        initExport();
        initViewModeToggle();
        initAutoSave();
        
        console.log('Kit Gamma: Initialized successfully');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
