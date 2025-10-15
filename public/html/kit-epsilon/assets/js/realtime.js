/**
 * Kit Epsilon - Real-time Data & Interactions
 * WebSocket connections, live updates, and dynamic content
 */

(function() {
    'use strict';

    const CONFIG = {
        websocket: {
            url: 'ws://localhost:8080',
            reconnectInterval: 5000,
            maxReconnectAttempts: 10
        },
        polling: {
            interval: 30000 // 30 seconds
        }
    };

    const state = {
        ws: null,
        reconnectAttempts: 0,
        pollingIntervals: {}
    };

    /**
     * Initialize real-time features
     */
    function init() {
        console.log('[Realtime] Initializing...');
        
        if ('WebSocket' in window) {
            initWebSocket();
        } else {
            console.warn('[Realtime] WebSocket not supported, using polling');
            initPolling();
        }
        
        initDragDrop();
        initLiveMetrics();
    }

    /**
     * WebSocket Connection
     */
    function initWebSocket() {
        try {
            state.ws = new WebSocket(CONFIG.websocket.url);
            
            state.ws.onopen = () => {
                console.log('[WebSocket] Connected');
                state.reconnectAttempts = 0;
                subscribeToChannels();
            };
            
            state.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    handleRealtimeUpdate(data);
                } catch (error) {
                    console.error('[WebSocket] Parse error:', error);
                }
            };
            
            state.ws.onerror = (error) => {
                console.error('[WebSocket] Error:', error);
            };
            
            state.ws.onclose = () => {
                console.log('[WebSocket] Disconnected');
                attemptReconnect();
            };
        } catch (error) {
            console.error('[WebSocket] Connection failed:', error);
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

    function subscribeToChannels() {
        if (state.ws && state.ws.readyState === WebSocket.OPEN) {
            state.ws.send(JSON.stringify({
                action: 'subscribe',
                channels: ['metrics', 'tasks', 'messages', 'team']
            }));
        }
    }

    function handleRealtimeUpdate(data) {
        console.log('[Realtime] Update received:', data.type);
        
        switch(data.type) {
            case 'metric':
                updateMetric(data.payload);
                break;
            case 'task':
                updateTask(data.payload);
                break;
            case 'message':
                addMessage(data.payload);
                break;
            case 'team':
                updateTeamStatus(data.payload);
                break;
            default:
                console.warn('[Realtime] Unknown update type:', data.type);
        }
    }

    /**
     * Polling Fallback
     */
    function initPolling() {
        console.log('[Polling] Starting polling...');
        
        state.pollingIntervals.metrics = setInterval(() => {
            fetchMetrics();
        }, CONFIG.polling.interval);
        
        state.pollingIntervals.tasks = setInterval(() => {
            fetchTasks();
        }, CONFIG.polling.interval);
        
        state.pollingIntervals.messages = setInterval(() => {
            fetchMessages();
        }, CONFIG.polling.interval * 2); // Less frequent
    }

    function fetchMetrics() {
        // Implement API call
        console.log('[Polling] Fetching metrics...');
    }

    function fetchTasks() {
        // Implement API call
        console.log('[Polling] Fetching tasks...');
    }

    function fetchMessages() {
        // Implement API call
        console.log('[Polling] Fetching messages...');
    }

    /**
     * Update Functions
     */
    function updateMetric(data) {
        const metricCard = document.querySelector(`.metric-card[data-id="${data.id}"]`);
        if (!metricCard) return;
        
        const valueElement = metricCard.querySelector('.metric-value');
        const changeElement = metricCard.querySelector('.metric-change');
        
        if (valueElement) {
            animateValue(valueElement, data.value);
        }
        
        if (changeElement && data.change) {
            changeElement.innerHTML = `
                <i class="bi bi-arrow-${data.change > 0 ? 'up' : 'down'}"></i>
                <span>${Math.abs(data.change)}%</span>
            `;
            changeElement.className = `metric-change ${data.change > 0 ? 'positive' : 'negative'}`;
        }
    }

    function updateTask(data) {
        console.log('[Task] Update:', data);
        // Implement task update logic
    }

    function addMessage(data) {
        const messageList = document.querySelector('.message-list');
        if (!messageList) return;
        
        const messageHTML = `
            <div class="message-item glass-card unread">
                <img src="${data.avatar}" alt="${data.sender}" class="message-avatar">
                <div class="message-content">
                    <div class="message-header">
                        <h4 class="message-sender">${data.sender}</h4>
                        <span class="message-time">Just now</span>
                    </div>
                    <p class="message-text">${data.message}</p>
                    <div class="message-actions">
                        <button class="btn-sm btn-glass">Reply</button>
                        <button class="btn-sm btn-text">Mark as read</button>
                    </div>
                </div>
            </div>
        `;
        
        messageList.insertAdjacentHTML('afterbegin', messageHTML);
        
        // Update badge count
        updateBadgeCount('.nav-action-btn[aria-label="Messages"] .action-badge', 1);
        
        // Announce to screen readers
        if (window.announceToScreenReader) {
            window.announceToScreenReader(`New message from ${data.sender}`);
        }
    }

    function updateTeamStatus(data) {
        const member = document.querySelector(`.team-member[data-id="${data.userId}"]`);
        if (!member) return;
        
        const presenceIndicator = member.querySelector('.member-presence');
        const statusElement = member.querySelector('.member-status');
        
        if (presenceIndicator) {
            presenceIndicator.className = `member-presence ${data.status}`;
        }
        
        if (statusElement && data.customStatus) {
            statusElement.textContent = data.customStatus;
        }
    }

    /**
     * Drag and Drop
     */
    function initDragDrop() {
        let draggedElement = null;
        
        // Make tasks draggable
        document.querySelectorAll('.task-item').forEach(task => {
            task.draggable = true;
            
            task.addEventListener('dragstart', (e) => {
                draggedElement = task;
                task.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });
            
            task.addEventListener('dragend', () => {
                task.style.opacity = '';
                draggedElement = null;
            });
        });
        
        // Make task list a drop zone
        document.querySelectorAll('.task-list').forEach(list => {
            list.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                const afterElement = getDragAfterElement(list, e.clientY);
                if (afterElement == null) {
                    list.appendChild(draggedElement);
                } else {
                    list.insertBefore(draggedElement, afterElement);
                }
            });
        });
    }

    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.task-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    /**
     * Live Metrics
     */
    function initLiveMetrics() {
        // Animate charts
        animateCharts();
        
        // Update metrics periodically
        setInterval(() => {
            updateLiveMetrics();
        }, 5000);
    }

    function animateCharts() {
        document.querySelectorAll('.mini-chart polyline').forEach(chart => {
            const length = chart.getTotalLength();
            chart.style.strokeDasharray = length;
            chart.style.strokeDashoffset = length;
            
            setTimeout(() => {
                chart.style.transition = 'stroke-dashoffset 1s ease-out';
                chart.style.strokeDashoffset = '0';
            }, 100);
        });
    }

    function updateLiveMetrics() {
        // Simulate live metric updates
        document.querySelectorAll('.metric-value').forEach(element => {
            // Add subtle pulse animation
            element.style.animation = 'pulse 0.5s ease-out';
            setTimeout(() => {
                element.style.animation = '';
            }, 500);
        });
    }

    /**
     * Utility Functions
     */
    function animateValue(element, targetValue) {
        const currentValue = parseFloat(element.textContent.replace(/[^0-9.-]+/g, ''));
        const duration = 1000;
        const steps = 60;
        const increment = (targetValue - currentValue) / steps;
        let current = currentValue;
        let step = 0;
        
        const interval = setInterval(() => {
            current += increment;
            step++;
            
            element.textContent = formatValue(current);
            
            if (step >= steps) {
                element.textContent = formatValue(targetValue);
                clearInterval(interval);
            }
        }, duration / steps);
    }

    function formatValue(value) {
        if (value > 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
        } else if (value > 1000) {
            return (value / 1000).toFixed(1) + 'K';
        }
        return Math.round(value).toString();
    }

    function updateBadgeCount(selector, increment) {
        const badge = document.querySelector(selector);
        if (!badge) return;
        
        const currentCount = parseInt(badge.textContent) || 0;
        const newCount = currentCount + increment;
        
        if (newCount > 0) {
            badge.textContent = newCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export API
    window.KitEpsilonRealtime = {
        updateMetric,
        addMessage,
        updateTeamStatus,
        state
    };

})();
