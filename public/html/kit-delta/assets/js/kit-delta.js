/**
 * Kit Delta - Neural Interface JavaScript
 * Core functionality for the Neural Interface dashboard
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        websocket: {
            url: 'ws://localhost:8080',
            reconnectInterval: 5000,
            maxReconnectAttempts: 10
        },
        refresh: {
            metrics: 5000,
            tasks: 30000,
            notifications: 15000
        }
    };

    // State management
    const state = {
        ws: null,
        reconnectAttempts: 0,
        commandPaletteOpen: false,
        sidePanelOpen: false
    };

    /**
     * Initialize the dashboard
     */
    function init() {
        console.log('[Kit Delta] Initializing Neural Interface...');
        
        initCommandPalette();
        initKeyboardShortcuts();
        initSparklines();
        initCountdowns();
        initSidePanel();
        initTaskInteractions();
        initNotifications();
        
        // Initialize real-time features if available
        if ('WebSocket' in window) {
            initWebSocket();
        } else {
            console.warn('[Kit Delta] WebSocket not supported, using polling');
            initPolling();
        }
        
        console.log('[Kit Delta] Initialization complete');
    }

    /**
     * Command Palette functionality
     */
    function initCommandPalette() {
        const paletteBtn = document.getElementById('commandPaletteBtn');
        const palette = document.getElementById('commandPalette');
        const paletteInput = document.getElementById('command-palette-input');
        const backdrop = palette?.querySelector('.command-palette-backdrop');
        
        if (!paletteBtn || !palette) return;
        
        // Open command palette
        paletteBtn.addEventListener('click', openCommandPalette);
        
        // Close on backdrop click
        backdrop?.addEventListener('click', closeCommandPalette);
        
        // Command input handling
        if (paletteInput) {
            paletteInput.addEventListener('input', handleCommandInput);
            paletteInput.addEventListener('keydown', handleCommandKeydown);
        }
        
        // Command item clicks
        palette.querySelectorAll('.command-item').forEach(item => {
            item.addEventListener('click', () => {
                executeCommand(item.dataset.action);
                closeCommandPalette();
            });
        });
    }

    function openCommandPalette() {
        const palette = document.getElementById('commandPalette');
        const paletteInput = document.getElementById('command-palette-input');
        
        if (palette) {
            palette.style.display = 'flex';
            state.commandPaletteOpen = true;
            
            setTimeout(() => {
                paletteInput?.focus();
            }, 100);
        }
    }

    function closeCommandPalette() {
        const palette = document.getElementById('commandPalette');
        const paletteInput = document.getElementById('command-palette-input');
        
        if (palette) {
            palette.style.display = 'none';
            state.commandPaletteOpen = false;
            
            if (paletteInput) {
                paletteInput.value = '';
            }
        }
    }

    function handleCommandInput(e) {
        const query = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.command-item');
        
        items.forEach(item => {
            const label = item.querySelector('.command-label')?.textContent.toLowerCase();
            if (label && label.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function handleCommandKeydown(e) {
        if (e.key === 'Escape') {
            closeCommandPalette();
        } else if (e.key === 'Enter') {
            const visibleItems = Array.from(document.querySelectorAll('.command-item'))
                .filter(item => item.style.display !== 'none');
            
            if (visibleItems.length > 0) {
                executeCommand(visibleItems[0].dataset.action);
                closeCommandPalette();
            }
        }
    }

    function executeCommand(action) {
        console.log('[Command] Executing:', action);
        
        switch(action) {
            case 'new-task':
                alert('Create new task - To be implemented');
                break;
            case 'new-message':
                alert('New message - To be implemented');
                break;
            case 'goto-dashboard':
                window.location.href = '#dashboard';
                break;
            case 'goto-tasks':
                window.location.href = '#tasks';
                break;
            default:
                console.warn('[Command] Unknown action:', action);
        }
    }

    /**
     * Keyboard shortcuts
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K for command palette
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openCommandPalette();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                closeCommandPalette();
                closeSidePanel();
            }
            
            // Ctrl/Cmd + B to toggle sidebar
            if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                e.preventDefault();
                toggleSidePanel();
            }
            
            // ? for help
            if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
                showKeyboardShortcuts();
            }
        });
    }

    function showKeyboardShortcuts() {
        alert('Keyboard Shortcuts:\n\n' +
              'Ctrl+K - Command Palette\n' +
              'Ctrl+B - Toggle Sidebar\n' +
              'Esc - Close Modal/Panel\n' +
              '? - Show this help');
    }

    /**
     * Sparkline charts
     */
    function initSparklines() {
        document.querySelectorAll('.sparkline').forEach(sparkline => {
            const svg = sparkline.querySelector('svg');
            const values = sparkline.dataset.values?.split(',').map(Number) || [];
            
            if (values.length === 0 || !svg) return;
            
            drawSparkline(svg, values);
        });
    }

    function drawSparkline(svg, values) {
        const width = svg.clientWidth;
        const height = svg.clientHeight;
        const padding = 2;
        
        const max = Math.max(...values);
        const min = Math.min(...values);
        const range = max - min || 1;
        
        const points = values.map((value, index) => {
            const x = (index / (values.length - 1)) * width;
            const y = height - padding - ((value - min) / range) * (height - 2 * padding);
            return `${x},${y}`;
        }).join(' ');
        
        const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        polyline.setAttribute('points', points);
        polyline.setAttribute('fill', 'none');
        polyline.setAttribute('stroke', 'currentColor');
        polyline.setAttribute('stroke-width', '2');
        
        svg.innerHTML = '';
        svg.appendChild(polyline);
    }

    /**
     * Countdown timers
     */
    function initCountdowns() {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    }

    function updateCountdowns() {
        document.querySelectorAll('.countdown').forEach(countdown => {
            const dueDate = new Date(countdown.dataset.due);
            const now = new Date();
            const diff = dueDate - now;
            
            if (diff <= 0) {
                countdown.innerHTML = '<i class="bi bi-exclamation-circle"></i> Overdue';
                countdown.classList.add('overdue');
                countdown.dataset.urgent = 'true';
                return;
            }
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            const timeStr = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            countdown.innerHTML = `<i class="bi bi-clock"></i> ${timeStr}`;
            
            if (hours < 1) {
                countdown.dataset.urgent = 'true';
            }
        });
    }

    /**
     * Side panel
     */
    function initSidePanel() {
        const closeBtn = document.querySelector('.close-panel');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', closeSidePanel);
        }
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Load tab content (placeholder)
                console.log('[Side Panel] Switching to tab:', btn.dataset.tab);
            });
        });
    }

    function toggleSidePanel() {
        const panel = document.getElementById('sidePanel');
        if (panel) {
            panel.classList.toggle('open');
            state.sidePanelOpen = !state.sidePanelOpen;
        }
    }

    function closeSidePanel() {
        const panel = document.getElementById('sidePanel');
        if (panel) {
            panel.classList.remove('open');
            state.sidePanelOpen = false;
        }
    }

    /**
     * Task interactions
     */
    function initTaskInteractions() {
        // Task checkbox handling
        document.querySelectorAll('.task-checkbox input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const taskItem = e.target.closest('.task-item');
                
                if (e.target.checked) {
                    taskItem.style.opacity = '0.5';
                    setTimeout(() => {
                        console.log('[Task] Completed:', taskItem.querySelector('.task-title')?.textContent);
                        // taskItem.remove(); // Uncomment to remove completed tasks
                    }, 300);
                } else {
                    taskItem.style.opacity = '1';
                }
            });
        });
        
        // Terminal input
        const terminalInput = document.querySelector('.terminal-input');
        if (terminalInput) {
            terminalInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    handleTerminalCommand(e.target.value);
                    e.target.value = '';
                }
            });
        }
    }

    function handleTerminalCommand(command) {
        console.log('[Terminal] Command:', command);
        
        if (command.startsWith('/help')) {
            alert('Available commands:\n' +
                  '/task create - Create new task\n' +
                  '/task list - List all tasks\n' +
                  '/task assign @user - Assign task\n' +
                  '/task close #id - Close task');
        } else if (command.startsWith('/task')) {
            alert('Task command: ' + command);
        } else {
            alert('Unknown command. Type /help for available commands.');
        }
    }

    /**
     * Notifications
     */
    function initNotifications() {
        // Notification actions
        document.querySelectorAll('.notification-item').forEach(item => {
            const dismissBtn = item.querySelector('.notification-actions .btn-icon-sm:last-child');
            
            if (dismissBtn) {
                dismissBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    item.style.animation = 'fadeOut 0.3s ease-out';
                    setTimeout(() => item.remove(), 300);
                });
            }
        });
    }

    /**
     * WebSocket connection for real-time updates
     */
    function initWebSocket() {
        try {
            state.ws = new WebSocket(CONFIG.websocket.url);
            
            state.ws.onopen = () => {
                console.log('[WebSocket] Connected');
                state.reconnectAttempts = 0;
            };
            
            state.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                handleRealtimeUpdate(data);
            };
            
            state.ws.onerror = (error) => {
                console.error('[WebSocket] Error:', error);
            };
            
            state.ws.onclose = () => {
                console.log('[WebSocket] Disconnected');
                attemptReconnect();
            };
        } catch (error) {
            console.error('[WebSocket] Failed to connect:', error);
            initPolling();
        }
    }

    function attemptReconnect() {
        if (state.reconnectAttempts < CONFIG.websocket.maxReconnectAttempts) {
            state.reconnectAttempts++;
            console.log(`[WebSocket] Reconnecting... (${state.reconnectAttempts}/${CONFIG.websocket.maxReconnectAttempts})`);
            
            setTimeout(() => {
                initWebSocket();
            }, CONFIG.websocket.reconnectInterval);
        } else {
            console.warn('[WebSocket] Max reconnect attempts reached, falling back to polling');
            initPolling();
        }
    }

    function handleRealtimeUpdate(data) {
        console.log('[WebSocket] Update received:', data);
        
        switch(data.type) {
            case 'metric':
                updateMetric(data);
                break;
            case 'task':
                updateTask(data);
                break;
            case 'notification':
                addNotification(data);
                break;
            case 'event':
                addEvent(data);
                break;
            default:
                console.warn('[WebSocket] Unknown update type:', data.type);
        }
    }

    function updateMetric(data) {
        // Update metric card with new data
        console.log('[Metric] Update:', data);
    }

    function updateTask(data) {
        // Update task in the list
        console.log('[Task] Update:', data);
    }

    function addNotification(data) {
        // Add new notification to the list
        console.log('[Notification] New:', data);
    }

    function addEvent(data) {
        // Add event to the stream
        const eventStream = document.querySelector('.event-stream');
        if (!eventStream) return;
        
        const eventItem = document.createElement('div');
        eventItem.className = 'event-item';
        eventItem.dataset.type = data.level || 'info';
        eventItem.innerHTML = `
            <span class="event-time">${new Date().toLocaleTimeString('en-US', {hour12: false})}</span>
            <span class="event-icon"><i class="bi bi-${getEventIcon(data.level)}"></i></span>
            <span class="event-message">${data.message}</span>
        `;
        
        eventStream.insertBefore(eventItem, eventStream.firstChild);
        
        // Limit to last 10 events
        if (eventStream.children.length > 10) {
            eventStream.lastChild.remove();
        }
    }

    function getEventIcon(level) {
        const icons = {
            success: 'check-circle',
            warning: 'exclamation-triangle',
            error: 'x-circle',
            info: 'info-circle'
        };
        return icons[level] || 'info-circle';
    }

    /**
     * Polling fallback
     */
    function initPolling() {
        setInterval(() => {
            fetchMetrics();
        }, CONFIG.refresh.metrics);
        
        setInterval(() => {
            fetchTasks();
        }, CONFIG.refresh.tasks);
        
        setInterval(() => {
            fetchNotifications();
        }, CONFIG.refresh.notifications);
    }

    function fetchMetrics() {
        // Placeholder for API call
        console.log('[Polling] Fetching metrics...');
    }

    function fetchTasks() {
        // Placeholder for API call
        console.log('[Polling] Fetching tasks...');
    }

    function fetchNotifications() {
        // Placeholder for API call
        console.log('[Polling] Fetching notifications...');
    }

    /**
     * Utility functions
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

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for external access
    window.KitDelta = {
        openCommandPalette,
        closeCommandPalette,
        toggleSidePanel,
        state
    };

})();
