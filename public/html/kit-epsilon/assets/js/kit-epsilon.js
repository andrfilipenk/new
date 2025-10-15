/**
 * Kit Epsilon - Quantum Workspace JavaScript
 * Theme management, adaptive UI, and intelligent features
 */

(function() {
    'use strict';

    const CONFIG = {
        websocket: {
            url: 'ws://localhost:8080',
            reconnectInterval: 5000
        },
        theme: {
            storageKey: 'quantum-theme',
            default: 'auto'
        }
    };

    const state = {
        currentTheme: 'auto',
        systemTheme: 'light',
        sidebarOpen: true,
        ws: null
    };

    function init() {
        console.log('[Kit Epsilon] Initializing Quantum Workspace...');
        
        initTheme();
        initNavigation();
        initFilters();
        initTaskInteractions();
        initMessages();
        initTeam();
        initKeyboardShortcuts();
        initAccessibility();
        
        console.log('[Kit Epsilon] Initialization complete');
    }

    /**
     * Theme Management
     */
    function initTheme() {
        // Detect system theme
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        state.systemTheme = mediaQuery.matches ? 'dark' : 'light';
        
        // Load saved theme preference
        const savedTheme = localStorage.getItem(CONFIG.theme.storageKey) || CONFIG.theme.default;
        state.currentTheme = savedTheme;
        
        // Apply theme
        applyTheme(savedTheme);
        
        // Listen for system theme changes
        mediaQuery.addEventListener('change', (e) => {
            state.systemTheme = e.matches ? 'dark' : 'light';
            if (state.currentTheme === 'auto') {
                updateAutoTheme();
            }
        });
        
        // Theme toggle button
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', cycleTheme);
        }
    }

    function applyTheme(theme) {
        const body = document.body;
        body.setAttribute('data-theme', theme);
        
        if (theme === 'auto') {
            body.setAttribute('data-system-theme', state.systemTheme);
        }
        
        // Save preference
        localStorage.setItem(CONFIG.theme.storageKey, theme);
        
        // Announce to screen readers
        const themeName = theme === 'auto' ? `auto (${state.systemTheme})` : theme;
        announceToScreenReader(`Theme changed to ${themeName} mode`);
    }

    function cycleTheme() {
        const themes = ['light', 'dark', 'auto'];
        const currentIndex = themes.indexOf(state.currentTheme);
        const nextIndex = (currentIndex + 1) % themes.length;
        const nextTheme = themes[nextIndex];
        
        state.currentTheme = nextTheme;
        applyTheme(nextTheme);
    }

    function updateAutoTheme() {
        const body = document.body;
        body.setAttribute('data-system-theme', state.systemTheme);
    }

    /**
     * Navigation
     */
    function initNavigation() {
        // Mobile toggle
        const navToggle = document.querySelector('.nav-toggle');
        const sidebar = document.querySelector('.quantum-sidebar');
        
        if (navToggle && sidebar) {
            navToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                state.sidebarOpen = !state.sidebarOpen;
                navToggle.setAttribute('aria-expanded', state.sidebarOpen);
            });
        }
        
        // Sidebar links
        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Update active state
                document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                
                // Handle navigation
                const module = link.dataset.module;
                console.log('[Navigation] Switched to:', module);
                
                // Close mobile sidebar
                if (window.innerWidth < 768) {
                    sidebar?.classList.remove('open');
                }
            });
        });
        
        // Search functionality
        const searchInput = document.querySelector('.nav-search input');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                handleSearch(e.target.value);
            }, 300));
        }
    }

    function handleSearch(query) {
        if (query.length < 2) return;
        console.log('[Search] Query:', query);
        // Implement search logic here
    }

    /**
     * Task Filters
     */
    function initFilters() {
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                // Update active state
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                
                // Filter tasks
                const filter = chip.dataset.filter;
                filterTasks(filter);
            });
        });
        
        // View switcher
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const view = btn.dataset.view;
                switchView(view);
            });
        });
    }

    function filterTasks(filter) {
        console.log('[Tasks] Filtering by:', filter);
        const tasks = document.querySelectorAll('.task-item');
        
        tasks.forEach(task => {
            // Show all tasks or implement filter logic
            task.style.display = 'flex';
        });
    }

    function switchView(view) {
        console.log('[Tasks] Switching to view:', view);
        // Implement view switching logic (list vs kanban)
    }

    /**
     * Task Interactions
     */
    function initTaskInteractions() {
        // Checkbox handling
        document.querySelectorAll('.task-checkbox input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const taskItem = e.target.closest('.task-item');
                
                if (e.target.checked) {
                    taskItem.style.opacity = '0.6';
                    taskItem.style.textDecoration = 'line-through';
                    
                    setTimeout(() => {
                        console.log('[Task] Completed');
                        announceToScreenReader('Task marked as complete');
                    }, 300);
                } else {
                    taskItem.style.opacity = '1';
                    taskItem.style.textDecoration = 'none';
                }
            });
        });
        
        // Task item click
        document.querySelectorAll('.task-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    showTaskDetails(item);
                }
            });
        });
    }

    function showTaskDetails(taskElement) {
        const title = taskElement.querySelector('.task-title')?.textContent;
        console.log('[Task] Showing details for:', title);
        // Implement task details modal/panel
    }

    /**
     * Message Interactions
     */
    function initMessages() {
        // Mark as read
        document.querySelectorAll('.message-item').forEach(message => {
            const markReadBtn = message.querySelector('.btn-text');
            
            if (markReadBtn && markReadBtn.textContent.includes('Mark as read')) {
                markReadBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    message.classList.remove('unread');
                    markReadBtn.remove();
                    announceToScreenReader('Message marked as read');
                });
            }
            
            // Reply button
            const replyBtn = message.querySelector('.btn-glass');
            if (replyBtn) {
                replyBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    handleReply(message);
                });
            }
        });
        
        // Mark all read
        const markAllBtn = document.querySelector('.panel-header .btn-text');
        if (markAllBtn && markAllBtn.textContent.includes('Mark all read')) {
            markAllBtn.addEventListener('click', () => {
                document.querySelectorAll('.message-item.unread').forEach(msg => {
                    msg.classList.remove('unread');
                });
                announceToScreenReader('All messages marked as read');
            });
        }
    }

    function handleReply(messageElement) {
        const sender = messageElement.querySelector('.message-sender')?.textContent;
        console.log('[Message] Replying to:', sender);
        // Implement reply functionality
    }

    /**
     * Team Interactions
     */
    function initTeam() {
        document.querySelectorAll('.team-member').forEach(member => {
            member.addEventListener('click', () => {
                const name = member.querySelector('.member-name')?.textContent;
                console.log('[Team] Viewing profile:', name);
                // Implement profile view
            });
        });
    }

    /**
     * Keyboard Shortcuts
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('.nav-search input')?.focus();
            }
            
            // Ctrl/Cmd + B for sidebar toggle
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                const sidebar = document.querySelector('.quantum-sidebar');
                sidebar?.classList.toggle('open');
            }
            
            // Ctrl/Cmd + T for theme toggle
            if ((e.ctrlKey || e.metaKey) && e.key === 't') {
                e.preventDefault();
                cycleTheme();
            }
            
            // Escape to close modals/panels
            if (e.key === 'Escape') {
                closeModals();
            }
        });
    }

    function closeModals() {
        // Close any open modals or panels
        console.log('[UI] Closing modals');
    }

    /**
     * Accessibility
     */
    function initAccessibility() {
        // Create live region for announcements
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = 'position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden';
        document.body.appendChild(liveRegion);
        
        window.announceToScreenReader = (message) => {
            liveRegion.textContent = message;
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        };
        
        // Focus management
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-nav');
        });
        
        // Add focus styles
        const style = document.createElement('style');
        style.textContent = `
            .keyboard-nav *:focus {
                outline: 2px solid var(--accent-indigo) !important;
                outline-offset: 2px !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Utility Functions
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function announceToScreenReader(message) {
        if (window.announceToScreenReader) {
            window.announceToScreenReader(message);
        }
    }

    /**
     * FAB Interactions
     */
    function initFAB() {
        const fab = document.querySelector('.fab');
        if (fab) {
            fab.addEventListener('click', () => {
                showQuickActions();
            });
        }
    }

    function showQuickActions() {
        console.log('[FAB] Showing quick actions');
        // Implement quick actions menu
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Initialize FAB
    initFAB();

    // Export API
    window.KitEpsilon = {
        setTheme: applyTheme,
        cycleTheme,
        state
    };

})();
