/**
 * Kit Nexus - Neural-Adaptive Interface
 * JavaScript Interactive Behaviors
 */

class NeuralInterface {
    constructor() {
        this.commandPaletteActive = false;
        this.currentFocusNode = null;
        this.synapticCards = [];
        this.userBehaviorPatterns = [];
        this.cognitiveLoadLevel = 0;
        
        this.init();
    }
    
    init() {
        this.initNeuralNavigationSphere();
        this.initCommandPalette();
        this.initSynapticCards();
        this.initActionSpheres();
        this.initMemoryStream();
        this.initAmbientStream();
        this.initCognitiveLoadMonitor();
        this.setupKeyboardShortcuts();
    }
    
    /* ===================================
       NEURAL NAVIGATION SPHERE
       =================================== */
    initNeuralNavigationSphere() {
        const navSphere = document.querySelector('.neural-nav-sphere');
        if (!navSphere) return;
        
        navSphere.addEventListener('click', () => {
            navSphere.classList.toggle('expanded');
        });
        
        // Create navigation nodes dynamically
        const nodesContainer = document.createElement('div');
        nodesContainer.className = 'neural-nav-nodes';
        
        const navItems = [
            { icon: '🏠', label: 'Dashboard', path: '/dashboard' },
            { icon: '📊', label: 'Analytics', path: '/analytics' },
            { icon: '💼', label: 'Projects', path: '/projects' },
            { icon: '👥', label: 'Team', path: '/team' },
            { icon: '📈', label: 'Reports', path: '/reports' },
            { icon: '⚙️', label: 'Settings', path: '/settings' },
            { icon: '💬', label: 'Messages', path: '/messages' },
            { icon: '🔔', label: 'Alerts', path: '/alerts' }
        ];
        
        navItems.forEach((item, index) => {
            const node = document.createElement('div');
            node.className = 'nav-node';
            node.innerHTML = item.icon;
            node.setAttribute('data-label', item.label);
            node.setAttribute('data-path', item.path);
            node.setAttribute('title', item.label);
            
            node.addEventListener('click', (e) => {
                e.stopPropagation();
                this.navigateToSection(item.path, item.label);
                navSphere.classList.remove('expanded');
            });
            
            nodesContainer.appendChild(node);
        });
        
        navSphere.appendChild(nodesContainer);
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!navSphere.contains(e.target)) {
                navSphere.classList.remove('expanded');
            }
        });
    }
    
    navigateToSection(path, label) {
        console.log(`Navigating to ${label}: ${path}`);
        this.logUserBehavior('navigation', { path, label, timestamp: Date.now() });
        // Implement actual navigation logic here
    }
    
    /* ===================================
       NEURAL COMMAND PALETTE
       =================================== */
    initCommandPalette() {
        const palette = document.querySelector('.neural-command-palette');
        if (!palette) this.createCommandPalette();
        
        this.commandPalette = document.querySelector('.neural-command-palette');
        this.commandInput = this.commandPalette?.querySelector('.command-input');
        this.commandSuggestions = this.commandPalette?.querySelector('.command-suggestions');
        
        if (this.commandInput) {
            this.commandInput.addEventListener('input', (e) => {
                this.updateCommandSuggestions(e.target.value);
            });
            
            this.commandInput.addEventListener('keydown', (e) => {
                this.handleCommandKeyboard(e);
            });
        }
    }
    
    createCommandPalette() {
        const palette = document.createElement('div');
        palette.className = 'neural-command-palette';
        palette.innerHTML = `
            <input type="text" class="command-input" placeholder="🧠 Neural command or search..." autocomplete="off">
            <div class="command-suggestions"></div>
        `;
        document.body.appendChild(palette);
    }
    
    toggleCommandPalette() {
        if (!this.commandPalette) return;
        
        this.commandPaletteActive = !this.commandPaletteActive;
        this.commandPalette.classList.toggle('active', this.commandPaletteActive);
        
        if (this.commandPaletteActive) {
            this.commandInput.value = '';
            this.commandInput.focus();
            this.updateCommandSuggestions('');
        }
    }
    
    updateCommandSuggestions(query) {
        const commands = this.getPredictedCommands(query);
        
        if (!this.commandSuggestions) return;
        
        this.commandSuggestions.innerHTML = commands.map((cmd, index) => `
            <div class="command-item ${index === 0 ? 'selected' : ''}" data-index="${index}">
                <div class="command-label">
                    <span class="command-icon">${cmd.icon}</span>
                    <span>${cmd.label}</span>
                </div>
                ${cmd.shortcut ? `<span class="command-shortcut">${cmd.shortcut}</span>` : ''}
            </div>
        `).join('');
        
        // Add click handlers
        this.commandSuggestions.querySelectorAll('.command-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.executeCommand(commands[index]);
            });
        });
    }
    
    getPredictedCommands(query) {
        const allCommands = [
            { icon: '📊', label: 'Show me revenue trends', action: 'showRevenueTrends', confidence: 0.95 },
            { icon: '✅', label: 'Create new task', action: 'createTask', shortcut: 'Ctrl+N', confidence: 0.90 },
            { icon: '📈', label: 'Open analytics dashboard', action: 'openAnalytics', confidence: 0.85 },
            { icon: '👥', label: 'View team activity', action: 'viewTeamActivity', confidence: 0.80 },
            { icon: '📁', label: 'Create new project', action: 'createProject', confidence: 0.75 },
            { icon: '🔍', label: 'Search documents', action: 'searchDocuments', confidence: 0.70 },
            { icon: '⚙️', label: 'Open settings', action: 'openSettings', confidence: 0.65 },
            { icon: '📤', label: 'Export report', action: 'exportReport', confidence: 0.60 }
        ];
        
        if (!query) {
            // Return contextual commands based on current view and time
            return this.getContextualCommands(allCommands);
        }
        
        // Filter based on query
        const filtered = allCommands.filter(cmd => 
            cmd.label.toLowerCase().includes(query.toLowerCase())
        );
        
        return filtered.slice(0, 8);
    }
    
    getContextualCommands(commands) {
        // Sort by confidence (simulating AI prediction)
        return commands.sort((a, b) => b.confidence - a.confidence).slice(0, 8);
    }
    
    handleCommandKeyboard(e) {
        const items = this.commandSuggestions.querySelectorAll('.command-item');
        const selected = this.commandSuggestions.querySelector('.command-item.selected');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (selected && selected.nextElementSibling) {
                    selected.classList.remove('selected');
                    selected.nextElementSibling.classList.add('selected');
                }
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (selected && selected.previousElementSibling) {
                    selected.classList.remove('selected');
                    selected.previousElementSibling.classList.add('selected');
                }
                break;
            case 'Enter':
                e.preventDefault();
                if (selected) {
                    const index = parseInt(selected.getAttribute('data-index'));
                    const commands = this.getPredictedCommands(this.commandInput.value);
                    this.executeCommand(commands[index]);
                }
                break;
            case 'Escape':
                this.toggleCommandPalette();
                break;
        }
    }
    
    executeCommand(command) {
        console.log('Executing command:', command);
        this.toggleCommandPalette();
        this.logUserBehavior('command', { action: command.action, timestamp: Date.now() });
        
        // Implement actual command execution
        switch(command.action) {
            case 'createTask':
                this.createTask();
                break;
            case 'showRevenueTrends':
                this.showRevenueTrends();
                break;
            // Add more command handlers
        }
    }
    
    /* ===================================
       SYNAPTIC DATA CARDS
       =================================== */
    initSynapticCards() {
        const cards = document.querySelectorAll('.synaptic-card');
        
        cards.forEach(card => {
            // Hover activation
            card.addEventListener('mouseenter', () => {
                card.classList.add('state-activated');
                card.classList.remove('state-resting');
            });
            
            card.addEventListener('mouseleave', () => {
                card.classList.remove('state-activated');
                card.classList.add('state-resting');
            });
            
            // Simulate data updates
            this.simulateCardUpdates(card);
        });
        
        this.synapticCards = Array.from(cards);
    }
    
    simulateCardUpdates(card) {
        // Randomly trigger "firing" state to simulate real-time updates
        const updateInterval = 5000 + Math.random() * 10000; // 5-15 seconds
        
        setInterval(() => {
            card.classList.add('state-firing');
            
            // Update card value
            const valueElement = card.querySelector('.synaptic-value');
            if (valueElement) {
                const currentValue = parseFloat(valueElement.textContent.replace(/[^0-9.]/g, ''));
                const change = (Math.random() - 0.5) * 10;
                const newValue = (currentValue + change).toFixed(1);
                
                valueElement.textContent = newValue;
            }
            
            setTimeout(() => {
                card.classList.remove('state-firing');
            }, 1000);
        }, updateInterval);
    }
    
    /* ===================================
       PREDICTIVE ACTION SPHERES
       =================================== */
    initActionSpheres() {
        const container = document.querySelector('.action-spheres-container');
        if (!container) this.createActionSpheresContainer();
        
        // Generate action spheres based on context
        this.updateActionSpheres();
        
        // Update spheres periodically based on user behavior
        setInterval(() => {
            this.updateActionSpheres();
        }, 30000); // Every 30 seconds
    }
    
    createActionSpheresContainer() {
        const container = document.createElement('div');
        container.className = 'action-spheres-container';
        document.body.appendChild(container);
    }
    
    updateActionSpheres() {
        const container = document.querySelector('.action-spheres-container');
        if (!container) return;
        
        const predictions = this.getPredictedActions();
        
        container.innerHTML = predictions.map(action => `
            <div class="action-sphere category-${action.category}" 
                 data-confidence="${action.confidence}%"
                 data-action="${action.action}"
                 title="${action.label}">
                ${action.icon}
            </div>
        `).join('');
        
        // Add click handlers
        container.querySelectorAll('.action-sphere').forEach(sphere => {
            sphere.addEventListener('click', () => {
                const action = sphere.getAttribute('data-action');
                this.executeAction(action);
            });
        });
    }
    
    getPredictedActions() {
        // Simulate AI prediction of likely next actions
        const allActions = [
            { icon: '📊', label: 'Generate Report', action: 'generateReport', category: 'analysis', confidence: 92 },
            { icon: '✉️', label: 'Email Team', action: 'emailTeam', category: 'communication', confidence: 87 },
            { icon: '✅', label: 'Approve Budget', action: 'approveBudget', category: 'approval', confidence: 78 },
            { icon: '➕', label: 'Create Project', action: 'createProject', category: 'creation', confidence: 65 },
            { icon: '⚠️', label: 'Review Alert', action: 'reviewAlert', category: 'alert', confidence: 95 }
        ];
        
        // Filter based on confidence threshold and current context
        return allActions
            .filter(action => action.confidence > 60)
            .sort((a, b) => b.confidence - a.confidence)
            .slice(0, 4);
    }
    
    executeAction(action) {
        console.log('Executing predicted action:', action);
        this.logUserBehavior('predictiveAction', { action, timestamp: Date.now() });
        // Implement action execution
    }
    
    /* ===================================
       MEMORY STREAM TIMELINE
       =================================== */
    initMemoryStream() {
        const stream = document.querySelector('.memory-stream');
        if (!stream) return;
        
        const ribbon = stream.querySelector('.memory-ribbon');
        if (!ribbon) return;
        
        // Generate memory snapshots
        this.generateMemorySnapshots(ribbon);
        
        // Add interaction
        ribbon.querySelectorAll('.memory-snapshot').forEach(snapshot => {
            snapshot.addEventListener('click', () => {
                const timestamp = snapshot.getAttribute('data-timestamp');
                this.restoreMemoryState(timestamp);
            });
        });
    }
    
    generateMemorySnapshots(ribbon) {
        const now = Date.now();
        const snapshots = [];
        
        // Generate snapshots for last 2 hours
        for (let i = 0; i < 20; i++) {
            const timestamp = now - (i * 6 * 60 * 1000); // 6-minute intervals
            snapshots.push({
                timestamp,
                timeLabel: this.formatTimeAgo(timestamp)
            });
        }
        
        ribbon.innerHTML = snapshots.map(snap => `
            <div class="memory-snapshot" data-timestamp="${snap.timestamp}">
                <span class="snapshot-time">${snap.timeLabel}</span>
            </div>
        `).join('');
    }
    
    formatTimeAgo(timestamp) {
        const minutes = Math.floor((Date.now() - timestamp) / 60000);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        return `${hours}h ago`;
    }
    
    restoreMemoryState(timestamp) {
        console.log('Restoring memory state from:', new Date(parseInt(timestamp)));
        // Implement state restoration
    }
    
    /* ===================================
       AMBIENT INTELLIGENCE STREAM
       =================================== */
    initAmbientStream() {
        const stream = document.querySelector('.ambient-stream');
        if (!stream) return;
        
        const container = stream.querySelector('.stream-container');
        if (!container) return;
        
        // Generate particles periodically
        setInterval(() => {
            this.createIntelligenceParticle(container);
        }, 3000); // Every 3 seconds
    }
    
    createIntelligenceParticle(container) {
        const particleTypes = [
            { class: 'particle-notification', icon: '💬', type: 'notification' },
            { class: 'particle-alert', icon: '⚠️', type: 'alert' },
            { class: 'particle-insight', icon: '💡', type: 'insight' }
        ];
        
        const randomType = particleTypes[Math.floor(Math.random() * particleTypes.length)];
        
        const particle = document.createElement('div');
        particle.className = `intelligence-particle ${randomType.class}`;
        particle.innerHTML = randomType.icon;
        particle.setAttribute('data-type', randomType.type);
        
        particle.addEventListener('click', () => {
            this.expandIntelligenceParticle(particle, randomType.type);
        });
        
        container.appendChild(particle);
        
        // Remove after animation completes
        setTimeout(() => {
            particle.remove();
        }, 10000);
    }
    
    expandIntelligenceParticle(particle, type) {
        console.log('Expanding intelligence particle:', type);
        // Implement particle expansion to full card
    }
    
    /* ===================================
       COGNITIVE LOAD MONITOR
       =================================== */
    initCognitiveLoadMonitor() {
        // Monitor user interaction patterns
        let idleTime = 0;
        let interactionCount = 0;
        
        // Reset idle time on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => {
                idleTime = 0;
                interactionCount++;
            });
        });
        
        // Check cognitive load every second
        setInterval(() => {
            idleTime++;
            
            if (idleTime > 10) {
                // User seems idle, reduce interface complexity
                this.adjustCognitiveLoad('low');
            } else if (interactionCount > 50) {
                // High activity, user is engaged
                this.adjustCognitiveLoad('high');
                interactionCount = 0;
            }
        }, 1000);
    }
    
    adjustCognitiveLoad(level) {
        const cards = document.querySelectorAll('.cognitive-card.ambient-layer');
        
        switch(level) {
            case 'low':
                // Fade out ambient information
                cards.forEach(card => {
                    card.style.opacity = '0.3';
                });
                break;
            case 'high':
                // Show all information
                cards.forEach(card => {
                    card.style.opacity = '0.6';
                });
                break;
        }
        
        this.cognitiveLoadLevel = level;
    }
    
    /* ===================================
       KEYBOARD SHORTCUTS
       =================================== */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Command Palette: Ctrl/Cmd + Space
            if ((e.ctrlKey || e.metaKey) && e.code === 'Space') {
                e.preventDefault();
                this.toggleCommandPalette();
            }
            
            // Navigate to central focus: Space (when not in input)
            if (e.code === 'Space' && !this.isInputFocused()) {
                e.preventDefault();
                this.returnToFocusNode();
            }
            
            // Undo navigation: Ctrl/Cmd + Z
            if ((e.ctrlKey || e.metaKey) && e.code === 'KeyZ' && !e.shiftKey) {
                e.preventDefault();
                this.navigateBackward();
            }
            
            // Redo navigation: Ctrl/Cmd + Shift + Z
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.code === 'KeyZ') {
                e.preventDefault();
                this.navigateForward();
            }
        });
    }
    
    isInputFocused() {
        const activeElement = document.activeElement;
        return activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.isContentEditable
        );
    }
    
    returnToFocusNode() {
        console.log('Returning to central focus node');
        // Implement focus node navigation
    }
    
    navigateBackward() {
        console.log('Navigating backward in memory stream');
        // Implement backward navigation
    }
    
    navigateForward() {
        console.log('Navigating forward in memory stream');
        // Implement forward navigation
    }
    
    /* ===================================
       USER BEHAVIOR TRACKING
       =================================== */
    logUserBehavior(eventType, data) {
        const event = {
            type: eventType,
            data,
            timestamp: Date.now()
        };
        
        this.userBehaviorPatterns.push(event);
        
        // Keep only last 100 events
        if (this.userBehaviorPatterns.length > 100) {
            this.userBehaviorPatterns.shift();
        }
        
        // Analyze patterns for predictions
        this.analyzeUserPatterns();
    }
    
    analyzeUserPatterns() {
        // Implement pattern analysis for predictive features
        // This would use ML models in production
        console.log('Analyzing user behavior patterns...');
    }
    
    /* ===================================
       UTILITY METHODS
       =================================== */
    createTask() {
        console.log('Creating new task...');
        // Implement task creation
    }
    
    showRevenueTrends() {
        console.log('Showing revenue trends...');
        // Implement revenue trends display
    }
}

// Initialize Neural Interface when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.neuralInterface = new NeuralInterface();
    console.log('🧠 Neural Interface initialized');
});
