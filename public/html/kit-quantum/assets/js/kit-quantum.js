/**
 * Kit Quantum - Probabilistic Decision Engine
 * JavaScript for probability calculations and quantum simulations
 */

class QuantumDecisionEngine {
    constructor() {
        this.scenarios = [];
        this.decisionTree = null;
        this.probabilityLandscape = null;
        this.currentState = 'superposition';
        
        this.init();
    }
    
    init() {
        this.initProbabilityLandscape();
        this.initDecisionTree();
        this.initProbabilityStreams();
        this.initSuperpositionViewer();
        this.initEntanglementNetwork();
        this.initCollapseSimulator();
        this.initTemporalNavigator();
        this.setupKeyboardShortcuts();
        
        console.log('⚛️ Quantum Decision Engine initialized');
    }
    
    /* ===================================
       PROBABILITY CALCULATIONS
       =================================== */
    calculateProbability(factors) {
        // Monte Carlo simulation for probability
        const iterations = 1000;
        let successCount = 0;
        
        for (let i = 0; i < iterations; i++) {
            let score = 0;
            factors.forEach(factor => {
                const random = Math.random();
                if (random < factor.weight) {
                    score += factor.value;
                }
            });
            
            if (score >= 0.5) successCount++;
        }
        
        return (successCount / iterations * 100).toFixed(1);
    }
    
    /* ===================================
       PROBABILITY LANDSCAPE
       =================================== */
    initProbabilityLandscape() {
        const landscape = document.querySelector('.probability-landscape');
        if (!landscape) return;
        
        const grid = document.querySelector('.terrain-grid');
        if (!grid) return;
        
        // Generate terrain cells with probability heights
        const cells = grid.querySelectorAll('.terrain-cell');
        cells.forEach((cell, index) => {
            const probability = this.generateProbabilityValue(index);
            const height = probability * 100;
            
            cell.style.setProperty('--cell-height', `${height}px`);
            
            if (probability > 0.7) {
                cell.classList.add('high-probability');
            } else if (probability < 0.3) {
                cell.classList.add('low-probability');
            }
            
            cell.addEventListener('click', () => {
                this.showProbabilityDetails(probability, index);
            });
        });
    }
    
    generateProbabilityValue(index) {
        // Generate probability using Perlin noise-like algorithm
        const x = index % 20;
        const y = Math.floor(index / 20);
        
        return (Math.sin(x / 3) + Math.cos(y / 3) + 2) / 4;
    }
    
    showProbabilityDetails(probability, index) {
        console.log(`Cell ${index}: Probability ${(probability * 100).toFixed(1)}%`);
        this.createQuantumNotification(
            `Probability: ${(probability * 100).toFixed(1)}%`,
            probability > 0.7 ? 'high' : probability < 0.3 ? 'low' : 'medium'
        );
    }
    
    /* ===================================
       QUANTUM DECISION TREE
       =================================== */
    initDecisionTree() {
        const treeContainer = document.querySelector('.decision-tree');
        if (!treeContainer) return;
        
        // Create decision tree structure
        const treeData = this.generateDecisionTreeData();
        this.renderDecisionTree(treeContainer, treeData);
    }
    
    generateDecisionTreeData() {
        return {
            root: {
                id: 'current',
                label: 'Current State',
                probability: 1.0,
                x: 50,
                y: 10
            },
            decisions: [
                { id: 'decision1', label: 'Strategy A', probability: 0.45, x: 30, y: 30 },
                { id: 'decision2', label: 'Strategy B', probability: 0.35, x: 50, y: 30 },
                { id: 'decision3', label: 'Strategy C', probability: 0.20, x: 70, y: 30 }
            ],
            outcomes: [
                { id: 'outcome1', label: 'Success', probability: 0.70, favorable: true, x: 20, y: 60, parent: 'decision1' },
                { id: 'outcome2', label: 'Moderate', probability: 0.50, favorable: true, x: 40, y: 60, parent: 'decision2' },
                { id: 'outcome3', label: 'Risk', probability: 0.30, favorable: false, x: 60, y: 60, parent: 'decision3' },
                { id: 'outcome4', label: 'Optimal', probability: 0.85, favorable: true, x: 80, y: 60, parent: 'decision1' }
            ]
        };
    }
    
    renderDecisionTree(container, data) {
        // Clear existing
        container.innerHTML = '';
        
        // Create SVG for branches
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.style.position = 'absolute';
        svg.style.width = '100%';
        svg.style.height = '100%';
        svg.style.pointerEvents = 'none';
        container.appendChild(svg);
        
        // Render root node
        this.createDecisionNode(container, data.root, 'node-current');
        
        // Render decision nodes and branches
        data.decisions.forEach(decision => {
            this.createDecisionNode(container, decision, 'node-decision');
            this.createBranch(svg, data.root, decision, decision.probability);
        });
        
        // Render outcome nodes and branches
        data.outcomes.forEach(outcome => {
            const nodeClass = `node-outcome ${outcome.favorable ? 'favorable' : 'unfavorable'}`;
            this.createDecisionNode(container, outcome, nodeClass);
            
            const parent = data.decisions.find(d => d.id === outcome.parent);
            if (parent) {
                this.createBranch(svg, parent, outcome, outcome.probability);
            }
        });
    }
    
    createDecisionNode(container, data, className) {
        const node = document.createElement('div');
        node.className = `decision-node ${className}`;
        node.style.left = `${data.x}%`;
        node.style.top = `${data.y}%`;
        node.style.transform = 'translate(-50%, -50%)';
        
        node.innerHTML = `
            <div class="node-label">${data.label}</div>
            <div class="node-probability">${(data.probability * 100).toFixed(0)}%</div>
        `;
        
        node.addEventListener('click', () => {
            this.selectDecisionNode(data);
        });
        
        container.appendChild(node);
    }
    
    createBranch(svg, from, to, probability) {
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        
        const fromX = from.x;
        const fromY = from.y;
        const toX = to.x;
        const toY = to.y;
        
        const d = `M ${fromX}% ${fromY}% Q ${(fromX + toX) / 2}% ${(fromY + toY) / 2}% ${toX}% ${toY}%`;
        
        path.setAttribute('d', d);
        path.setAttribute('class', `decision-branch ${probability > 0.6 ? 'high-probability' : ''}`);
        
        svg.appendChild(path);
    }
    
    selectDecisionNode(data) {
        console.log('Selected node:', data);
        this.createQuantumNotification(
            `${data.label}: ${(data.probability * 100).toFixed(1)}% probability`,
            'medium'
        );
    }
    
    /* ===================================
       PROBABILITY STREAMS
       =================================== */
    initProbabilityStreams() {
        const stream = document.querySelector('.stream-container');
        if (!stream) return;
        
        setInterval(() => {
            this.createFlowParticle(stream);
        }, 200);
    }
    
    createFlowParticle(container) {
        const particle = document.createElement('div');
        const probability = Math.random();
        
        let particleClass = 'particle-low-prob';
        if (probability > 0.7) particleClass = 'particle-high-prob';
        else if (probability > 0.4) particleClass = 'particle-medium-prob';
        
        particle.className = `flow-particle ${particleClass}`;
        
        const startX = Math.random() * 100;
        const flowX = (Math.random() - 0.5) * 100;
        
        particle.style.left = `${startX}%`;
        particle.style.setProperty('--flow-x', `${flowX}px`);
        particle.style.animationDelay = `${Math.random() * 2}s`;
        
        container.appendChild(particle);
        
        setTimeout(() => particle.remove(), 5000);
    }
    
    /* ===================================
       SUPERPOSITION VIEWER
       =================================== */
    initSuperpositionViewer() {
        this.generateScenarios();
    }
    
    generateScenarios() {
        this.scenarios = [
            {
                id: 'alpha',
                name: 'Aggressive Growth',
                probability: 0.35,
                metrics: {
                    revenue: '+45%',
                    risk: 'High',
                    investment: '$50M',
                    timeline: '18 months'
                }
            },
            {
                id: 'beta',
                name: 'Conservative Approach',
                probability: 0.60,
                metrics: {
                    revenue: '+15%',
                    risk: 'Low',
                    investment: '$10M',
                    timeline: '12 months'
                }
            },
            {
                id: 'gamma',
                name: 'Innovation Pivot',
                probability: 0.20,
                metrics: {
                    revenue: '+60%',
                    risk: 'Very High',
                    investment: '$75M',
                    timeline: '24 months'
                }
            }
        ];
    }
    
    /* ===================================
       ENTANGLEMENT NETWORK
       =================================== */
    initEntanglementNetwork() {
        const network = document.querySelector('.entanglement-network');
        if (!network) return;
        
        const nodes = [
            { id: 1, label: 'Revenue', x: 30, y: 30 },
            { id: 2, label: 'Market', x: 70, y: 30 },
            { id: 3, label: 'Costs', x: 30, y: 70 },
            { id: 4, label: 'Risk', x: 70, y: 70 },
            { id: 5, label: 'Growth', x: 50, y: 50 }
        ];
        
        const links = [
            { from: 1, to: 2, type: 'positive' },
            { from: 1, to: 3, type: 'negative' },
            { from: 2, to: 5, type: 'positive' },
            { from: 3, to: 4, type: 'positive' },
            { from: 4, to: 5, type: 'negative' }
        ];
        
        // Render nodes
        nodes.forEach(node => {
            const nodeEl = document.createElement('div');
            nodeEl.className = 'network-node';
            nodeEl.style.left = `${node.x}%`;
            nodeEl.style.top = `${node.y}%`;
            nodeEl.style.transform = 'translate(-50%, -50%)';
            nodeEl.textContent = node.label;
            nodeEl.setAttribute('data-id', node.id);
            
            network.appendChild(nodeEl);
        });
        
        // Render links
        links.forEach(link => {
            const fromNode = nodes.find(n => n.id === link.from);
            const toNode = nodes.find(n => n.id === link.to);
            
            if (fromNode && toNode) {
                this.createEntanglementLink(network, fromNode, toNode, link.type);
            }
        });
    }
    
    createEntanglementLink(container, from, to, type) {
        const link = document.createElement('div');
        link.className = `entanglement-link link-${type}`;
        
        const fromX = from.x / 100 * container.offsetWidth;
        const fromY = from.y / 100 * container.offsetHeight;
        const toX = to.x / 100 * container.offsetWidth;
        const toY = to.y / 100 * container.offsetHeight;
        
        const length = Math.sqrt(Math.pow(toX - fromX, 2) + Math.pow(toY - fromY, 2));
        const angle = Math.atan2(toY - fromY, toX - fromX) * 180 / Math.PI;
        
        link.style.width = `${length}px`;
        link.style.left = `${fromX}px`;
        link.style.top = `${fromY}px`;
        link.style.transform = `rotate(${angle}deg)`;
        
        container.appendChild(link);
    }
    
    /* ===================================
       STATE COLLAPSE SIMULATOR
       =================================== */
    initCollapseSimulator() {
        const collapseBtn = document.querySelector('.btn-collapse');
        if (!collapseBtn) return;
        
        collapseBtn.addEventListener('click', () => {
            this.performStateCollapse();
        });
    }
    
    performStateCollapse() {
        const visualization = document.querySelector('.collapse-visualization');
        if (!visualization) return;
        
        // Create collapse wave animation
        const wave = document.createElement('div');
        wave.className = 'collapse-wave';
        visualization.appendChild(wave);
        
        setTimeout(() => wave.remove(), 1000);
        
        // Select random scenario
        const selectedScenario = this.scenarios[Math.floor(Math.random() * this.scenarios.length)];
        
        this.createQuantumNotification(
            `State collapsed to: ${selectedScenario.name} (P=${(selectedScenario.probability * 100).toFixed(0)}%)`,
            'high'
        );
        
        this.currentState = 'collapsed';
    }
    
    /* ===================================
       TEMPORAL NAVIGATOR
       =================================== */
    initTemporalNavigator() {
        const marker = document.querySelector('.timeline-marker');
        if (!marker) return;
        
        const track = document.querySelector('.timeline-track');
        let isDragging = false;
        
        marker.addEventListener('mousedown', () => {
            isDragging = true;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging || !track) return;
            
            const rect = track.getBoundingClientRect();
            let position = (e.clientX - rect.left) / rect.width * 100;
            position = Math.max(0, Math.min(100, position));
            
            marker.style.left = `${position}%`;
            this.updateTemporalView(position);
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
    }
    
    updateTemporalView(position) {
        const timePoints = ['Past', 'Recent', 'Present', 'Near Future', 'Far Future'];
        const index = Math.floor(position / 25);
        const label = timePoints[Math.min(index, timePoints.length - 1)];
        
        console.log(`Temporal position: ${label} (${position.toFixed(0)}%)`);
    }
    
    /* ===================================
       NOTIFICATIONS
       =================================== */
    createQuantumNotification(message, urgency = 'medium') {
        const notification = document.createElement('div');
        notification.className = 'quantum-card';
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '10000';
        notification.style.minWidth = '300px';
        notification.style.animation = 'slideInRight 0.3s ease-out';
        
        const urgencyColors = {
            low: '#0066FF',
            medium: '#00FFFF',
            high: '#00FF00',
            critical: '#FF0000'
        };
        
        notification.style.borderColor = urgencyColors[urgency];
        notification.innerHTML = `
            <div class="card-title" style="color: ${urgencyColors[urgency]}">${urgency.toUpperCase()}</div>
            <div style="font-size: 14px; color: rgba(255,255,255,0.8);">${message}</div>
        `;
        
        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        });
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.click();
            }
        }, 5000);
    }
    
    /* ===================================
       KEYBOARD SHORTCUTS
       =================================== */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // C - Collapse state
            if (e.code === 'KeyC' && !this.isInputFocused()) {
                this.performStateCollapse();
            }
            
            // R - Restore superposition
            if (e.code === 'KeyR' && !this.isInputFocused()) {
                this.currentState = 'superposition';
                this.createQuantumNotification('Restored to superposition state', 'medium');
            }
            
            // S - Save scenario
            if (e.code === 'KeyS' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                this.saveCurrentState();
            }
        });
    }
    
    isInputFocused() {
        const active = document.activeElement;
        return active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');
    }
    
    saveCurrentState() {
        console.log('Saving quantum state...', this.scenarios);
        this.createQuantumNotification('Quantum state saved', 'high');
    }
}

// Add animations via CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.quantumEngine = new QuantumDecisionEngine();
});
