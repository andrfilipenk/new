/**
 * Kit Alpha: Executive Focus
 * Custom JavaScript for Dashboard Interactions
 */

(function() {
    'use strict';

    // ============================================
    // Sidebar Toggle Functionality
    // ============================================
    const initSidebarToggle = () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                
                // Save preference to localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('alpha-sidebar-collapsed', isCollapsed);
            });
            
            // Restore sidebar state from localStorage
            const savedState = localStorage.getItem('alpha-sidebar-collapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
            }
        }
    };

    // ============================================
    // Global Search Keyboard Shortcut
    // ============================================
    const initSearchShortcut = () => {
        const searchInput = document.getElementById('globalSearch');
        
        if (searchInput) {
            document.addEventListener('keydown', (e) => {
                // Ctrl+K or Cmd+K to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });
        }
    };

    // ============================================
    // Notification Real-time Updates
    // ============================================
    const initNotificationUpdates = () => {
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationDropdown) {
            // Simulated real-time update (replace with actual API call)
            const checkNewNotifications = () => {
                // In production, this would be an API call
                // fetch('/api/notifications?user={id}')
                //     .then(response => response.json())
                //     .then(data => updateNotificationBadge(data.count));
                
                console.log('Checking for new notifications...');
            };
            
            // Check every 60 seconds
            setInterval(checkNewNotifications, 60000);
        }
    };

    // ============================================
    // Task Quick Actions
    // ============================================
    const initTaskActions = () => {
        const taskList = document.querySelector('.alpha-task-list');
        
        if (taskList) {
            // Delegate event handling for task actions
            taskList.addEventListener('click', (e) => {
                const viewBtn = e.target.closest('.btn-outline-primary');
                const completeBtn = e.target.closest('.btn-outline-success');
                
                if (viewBtn) {
                    handleViewTask(e.target.closest('.alpha-task-item'));
                } else if (completeBtn) {
                    handleCompleteTask(e.target.closest('.alpha-task-item'));
                }
            });
        }
    };

    const handleViewTask = (taskItem) => {
        if (!taskItem) return;
        
        const taskTitle = taskItem.querySelector('.alpha-task-title').textContent;
        console.log('Viewing task:', taskTitle);
        
        // In production, navigate to task detail page or open modal
        // window.location.href = `/tasks/${taskId}`;
        
        alert(`View task: ${taskTitle}\n\n(In production, this would open the task detail view)`);
    };

    const handleCompleteTask = (taskItem) => {
        if (!taskItem) return;
        
        const taskTitle = taskItem.querySelector('.alpha-task-title').textContent;
        
        if (confirm(`Mark "${taskTitle}" as complete?`)) {
            console.log('Completing task:', taskTitle);
            
            // In production, make API call
            // fetch(`/api/tasks/${taskId}/complete`, { method: 'POST' })
            //     .then(response => response.json())
            //     .then(data => {
            //         // Update UI
            //         taskItem.remove();
            //         showSuccessToast('Task completed successfully');
            //     });
            
            // Simulate completion with visual feedback
            taskItem.style.opacity = '0.5';
            taskItem.querySelector('.alpha-task-status .badge').textContent = 'Completed';
            taskItem.querySelector('.alpha-task-status .badge').className = 'badge bg-success';
            
            setTimeout(() => {
                taskItem.remove();
                showSuccessToast('Task marked as complete');
            }, 500);
        }
    };

    // ============================================
    // Message Item Click Handler
    // ============================================
    const initMessageActions = () => {
        const messageList = document.querySelector('.alpha-message-list');
        
        if (messageList) {
            messageList.addEventListener('click', (e) => {
                const messageItem = e.target.closest('.alpha-message-item');
                if (messageItem) {
                    handleMessageClick(messageItem);
                }
            });
        }
    };

    const handleMessageClick = (messageItem) => {
        if (!messageItem) return;
        
        const sender = messageItem.querySelector('.alpha-message-sender').textContent;
        const subject = messageItem.querySelector('.alpha-message-subject').textContent;
        
        console.log('Opening message from:', sender);
        
        // Remove unread styling
        messageItem.classList.remove('alpha-message-unread');
        messageItem.querySelector('.alpha-message-sender').style.fontWeight = 'normal';
        messageItem.querySelector('.alpha-message-subject').style.fontWeight = 'normal';
        
        // In production, navigate to message or open modal
        alert(`From: ${sender}\nSubject: ${subject}\n\n(In production, this would open the full message)`);
    };

    // ============================================
    // Activity Filter Buttons
    // ============================================
    const initActivityFilters = () => {
        const filterButtons = document.querySelectorAll('.alpha-filter-buttons .btn');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                button.classList.add('active');
                
                const filter = button.textContent.trim();
                console.log('Filtering activity by:', filter);
                
                // In production, filter timeline items
                filterActivityTimeline(filter);
            });
        });
    };

    const filterActivityTimeline = (filter) => {
        const timelineItems = document.querySelectorAll('.alpha-timeline-item');
        
        if (filter === 'All') {
            timelineItems.forEach(item => item.style.display = 'flex');
            return;
        }
        
        // Simple filter simulation
        timelineItems.forEach(item => {
            const description = item.querySelector('.alpha-timeline-desc').textContent;
            if (description.includes(filter)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    };

    // ============================================
    // Toast Notification System
    // ============================================
    const showSuccessToast = (message) => {
        const toastContainer = getOrCreateToastContainer();
        
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-success border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 3000
        });
        
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    };

    const getOrCreateToastContainer = () => {
        let container = document.querySelector('.toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }
        
        return container;
    };

    // ============================================
    // Mark All Notifications as Read
    // ============================================
    const initMarkAllRead = () => {
        const markAllBtn = document.querySelector('.alpha-widget-header .btn-link');
        
        if (markAllBtn && markAllBtn.textContent.includes('Mark all read')) {
            markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                console.log('Marking all notifications as read');
                
                // In production, make API call
                // fetch('/api/notifications/mark-all-read', { method: 'POST' })
                //     .then(() => {
                //         // Update UI
                //         clearNotificationBadge();
                //         showSuccessToast('All notifications marked as read');
                //     });
                
                // Update notification badge
                const badge = document.querySelector('.alpha-navbar .bi-bell + .badge');
                if (badge) {
                    badge.remove();
                }
                
                showSuccessToast('All notifications marked as read');
            });
        }
    };

    // ============================================
    // Keyboard Shortcuts Modal
    // ============================================
    const initKeyboardShortcuts = () => {
        document.addEventListener('keydown', (e) => {
            // Ctrl+/ or Cmd+/ to show shortcuts
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                showKeyboardShortcuts();
            }
            
            // ESC to close modals
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                });
            }
        });
    };

    const showKeyboardShortcuts = () => {
        alert(`Keyboard Shortcuts:
        
Ctrl+K - Global search
Ctrl+/ - Show this help
G then D - Go to Dashboard
G then T - Go to Tasks
G then M - Go to Messages
ESC - Close modal/dialog

(In production, this would show a styled modal)`);
    };

    // ============================================
    // Relative Time Updates
    // ============================================
    const initRelativeTime = () => {
        const updateRelativeTimes = () => {
            document.querySelectorAll('[data-timestamp]').forEach(element => {
                const timestamp = parseInt(element.dataset.timestamp);
                const relativeTime = getRelativeTime(timestamp);
                element.textContent = relativeTime;
            });
        };
        
        // Update every minute
        updateRelativeTimes();
        setInterval(updateRelativeTimes, 60000);
    };

    const getRelativeTime = (timestamp) => {
        const now = Date.now();
        const diff = now - timestamp;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (days < 7) return `${days} day${days > 1 ? 's' : ''} ago`;
        
        return new Date(timestamp).toLocaleDateString();
    };

    // ============================================
    // Accessibility: Focus Management
    // ============================================
    const initFocusManagement = () => {
        // Trap focus in modals
        document.addEventListener('shown.bs.modal', (e) => {
            const modal = e.target;
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });
    };

    // ============================================
    // Auto-save Form State
    // ============================================
    const initAutoSave = () => {
        const forms = document.querySelectorAll('form[data-autosave]');
        
        forms.forEach(form => {
            const formId = form.id || form.name;
            
            // Restore saved data
            const savedData = localStorage.getItem(`form-${formId}`);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(key => {
                        const field = form.elements[key];
                        if (field) field.value = data[key];
                    });
                } catch (e) {
                    console.error('Failed to restore form data:', e);
                }
            }
            
            // Save on input
            form.addEventListener('input', debounce(() => {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData);
                localStorage.setItem(`form-${formId}`, JSON.stringify(data));
            }, 1000));
        });
    };

    // ============================================
    // Utility Functions
    // ============================================
    const debounce = (func, wait) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // ============================================
    // Initialize All Features
    // ============================================
    const init = () => {
        console.log('Kit Alpha: Executive Focus - Initializing...');
        
        initSidebarToggle();
        initSearchShortcut();
        initNotificationUpdates();
        initTaskActions();
        initMessageActions();
        initActivityFilters();
        initMarkAllRead();
        initKeyboardShortcuts();
        initRelativeTime();
        initFocusManagement();
        initAutoSave();
        
        console.log('Kit Alpha: Executive Focus - Initialized successfully');
    };

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
