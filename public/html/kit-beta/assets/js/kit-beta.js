/**
 * Kit Beta: Operational Efficiency
 * JavaScript for rapid task processing and real-time updates
 */

(function() {
    'use strict';

    // ============================================
    // Real-time Activity Monitor
    // ============================================
    const initActivityMonitor = () => {
        const activityFeed = document.querySelector('.beta-activity-feed');
        if (!activityFeed) return;

        let isPaused = false;
        const pauseBtn = document.querySelector('.beta-panel-header .btn-link');
        
        if (pauseBtn) {
            pauseBtn.addEventListener('click', () => {
                isPaused = !isPaused;
                pauseBtn.textContent = isPaused ? 'Resume' : 'Pause';
            });
        }

        // Simulate real-time updates
        setInterval(() => {
            if (isPaused) return;
            
            const activities = [
                { type: 'success', text: 'Task completed successfully' },
                { type: 'info', text: 'New user session started' },
                { type: 'warning', text: 'System resource at 75%' },
                { type: 'danger', text: 'API request failed' }
            ];
            
            // Randomly add new activity (10% chance each interval)
            if (Math.random() < 0.1) {
                const activity = activities[Math.floor(Math.random() * activities.length)];
                addActivityItem(activityFeed, activity);
            }
        }, 5000);
    };

    const addActivityItem = (feed, activity) => {
        const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        const item = document.createElement('div');
        item.className = 'beta-activity-item';
        item.innerHTML = `
            <span class="beta-activity-time">${time}</span>
            <span class="beta-activity-badge beta-badge-${activity.type}"></span>
            <span class="beta-activity-text">${activity.text}</span>
        `;
        
        feed.insertBefore(item, feed.firstChild);
        
        // Keep only last 10 items
        while (feed.children.length > 10) {
            feed.removeChild(feed.lastChild);
        }
    };

    // ============================================
    // Task Queue Management
    // ============================================
    const initTaskQueue = () => {
        const table = document.querySelector('.beta-table');
        if (!table) return;

        // Handle row clicks
        table.addEventListener('click', (e) => {
            const viewBtn = e.target.closest('.btn-outline-primary');
            const completeBtn = e.target.closest('.btn-outline-success');
            const claimBtn = e.target.closest('.btn-outline-info');
            
            if (viewBtn) handleViewTask(e.target.closest('tr'));
            if (completeBtn) handleCompleteTask(e.target.closest('tr'));
            if (claimBtn) handleClaimTask(e.target.closest('tr'));
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'j') selectNextTask();
            if (e.key === 'k') selectPreviousTask();
            if (e.key === 'Enter' && document.activeElement.tagName !== 'INPUT') {
                const selected = document.querySelector('tr.table-active');
                if (selected) handleViewTask(selected);
            }
        });
    };

    const handleViewTask = (row) => {
        if (!row) return;
        const taskName = row.querySelector('td:nth-child(3) strong').textContent;
        console.log('Viewing task:', taskName);
        alert(`Task Details:\n${taskName}\n\n(In production, this would open a detail modal)`);
    };

    const handleCompleteTask = (row) => {
        if (!row) return;
        const taskName = row.querySelector('td:nth-child(3) strong').textContent;
        
        if (confirm(`Mark task complete?\n${taskName}`)) {
            row.style.opacity = '0.5';
            setTimeout(() => {
                row.remove();
                showToast('Task completed', 'success');
                updateStats();
            }, 300);
        }
    };

    const handleClaimTask = (row) => {
        if (!row) return;
        const taskName = row.querySelector('td:nth-child(3) strong').textContent;
        
        if (confirm(`Claim this task?\n${taskName}`)) {
            const statusBadge = row.querySelector('.badge');
            statusBadge.className = 'badge bg-warning text-dark';
            statusBadge.textContent = 'Processing';
            
            const claimBtn = row.querySelector('.btn-outline-info');
            claimBtn.className = 'btn btn-xs btn-outline-success';
            claimBtn.textContent = 'Complete';
            
            showToast('Task claimed', 'info');
        }
    };

    const selectNextTask = () => {
        const rows = Array.from(document.querySelectorAll('.beta-table tbody tr'));
        const current = document.querySelector('tr.table-active');
        const currentIndex = rows.indexOf(current);
        const nextIndex = (currentIndex + 1) % rows.length;
        
        rows.forEach(r => r.classList.remove('table-active'));
        rows[nextIndex].classList.add('table-active');
        rows[nextIndex].scrollIntoView({ block: 'nearest' });
    };

    const selectPreviousTask = () => {
        const rows = Array.from(document.querySelectorAll('.beta-table tbody tr'));
        const current = document.querySelector('tr.table-active');
        const currentIndex = rows.indexOf(current);
        const prevIndex = currentIndex <= 0 ? rows.length - 1 : currentIndex - 1;
        
        rows.forEach(r => r.classList.remove('table-active'));
        rows[prevIndex].classList.add('table-active');
        rows[prevIndex].scrollIntoView({ block: 'nearest' });
    };

    // ============================================
    // Stats Update
    // ============================================
    const updateStats = () => {
        const queueStat = document.querySelector('.beta-stats-bar .beta-stat-item:first-child .beta-stat-value');
        const completedStat = document.querySelector('.beta-stats-bar .beta-stat-item:nth-child(3) .beta-stat-value');
        
        if (queueStat) {
            const current = parseInt(queueStat.textContent);
            queueStat.textContent = current - 1;
        }
        
        if (completedStat) {
            const current = parseInt(completedStat.textContent);
            completedStat.textContent = current + 1;
        }
    };

    // ============================================
    // Batch Operations
    // ============================================
    const initBatchOperations = () => {
        const selectAll = document.querySelector('.beta-table thead input[type="checkbox"]');
        const rowCheckboxes = document.querySelectorAll('.beta-table tbody input[type="checkbox"]');
        
        if (selectAll) {
            selectAll.addEventListener('change', (e) => {
                rowCheckboxes.forEach(cb => cb.checked = e.target.checked);
                updateBatchActions();
            });
        }
        
        rowCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBatchActions);
        });
    };

    const updateBatchActions = () => {
        const checked = document.querySelectorAll('.beta-table tbody input[type="checkbox"]:checked');
        console.log(`${checked.length} tasks selected`);
        // In production, show/hide batch action toolbar
    };

    // ============================================
    // Message Click Handler
    // ============================================
    const initMessages = () => {
        const messages = document.querySelectorAll('.beta-message-compact');
        
        messages.forEach(msg => {
            msg.addEventListener('click', () => {
                msg.classList.remove('beta-message-unread');
                const sender = msg.querySelector('strong').textContent;
                alert(`Message from ${sender}\n\n(In production, this would open the message)`);
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
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 2000 });
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
    // Keyboard Shortcuts Help
    // ============================================
    const initKeyboardShortcuts = () => {
        document.addEventListener('keydown', (e) => {
            if (e.key === '?' && e.shiftKey) {
                showShortcutsHelp();
            }
        });
    };

    const showShortcutsHelp = () => {
        alert(`Keyboard Shortcuts:
        
J - Next task
K - Previous task
Enter - View task
? - Show shortcuts

(In production, this would show a modal)`);
    };

    // ============================================
    // Auto-refresh
    // ============================================
    const initAutoRefresh = () => {
        setInterval(() => {
            console.log('Auto-refreshing data...');
            // In production: fetch new data from API
        }, 30000);
    };

    // ============================================
    // Initialize
    // ============================================
    const init = () => {
        console.log('Kit Beta: Operational Efficiency - Initializing...');
        
        initActivityMonitor();
        initTaskQueue();
        initBatchOperations();
        initMessages();
        initKeyboardShortcuts();
        initAutoRefresh();
        
        console.log('Kit Beta: Initialized successfully');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
