/**
 * Kit Nebula - Main Controller
 * Orchestrates all components and effects
 */

class KitNebula {
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.particleEngine = null;
        this.threeDEffects = null;
        this.components = {};
        this.config = {
            enableParticles: options.enableParticles !== false,
            enable3D: options.enable3D !== false,
            particleCount: options.particleCount || 100,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Check for reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.config.enableParticles = false;
            this.config.enable3D = false;
        }
        
        // Initialize particle engine
        if (this.config.enableParticles) {
            this.particleEngine = new ParticleEngine({
                maxParticles: this.config.particleCount
            });
            this.startAmbientParticles();
        }
        
        // Initialize 3D effects
        if (this.config.enable3D) {
            this.threeDEffects = new ThreeDEffects();
        }
        
        // Initialize components
        this.initializeComponents();
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    initializeComponents() {
        // Initialize Stellar Navigation Orb
        const orbElement = document.querySelector('.kit-nebula-stellar-orb');
        if (orbElement) {
            this.components.stellarOrb = new StellarNavigationOrb(orbElement, this);
        }
        
        // Initialize Nebula Data Cards
        const cardsContainer = document.querySelector('.kit-nebula-data-cards');
        if (cardsContainer) {
            this.components.dataCards = new NebulaDataCards(cardsContainer, this);
        }
        
        // Initialize Command Palette
        this.components.commandPalette = new AuroraCommandPalette(this);
        
        // Initialize Notification Stream
        const notifContainer = document.querySelector('.kit-nebula-notifications');
        if (notifContainer) {
            this.components.notifications = new StellarNotifications(notifContainer, this);
        }
    }
    
    setupEventListeners() {
        // Command palette keyboard shortcut (Ctrl+K)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.showCommandPalette();
            }
            
            if (e.key === 'Escape') {
                this.hideCommandPalette();
            }
        });
        
        // Particle effects on cursor
        if (this.config.enableParticles) {
            let lastEmit = 0;
            document.addEventListener('mousemove', (e) => {
                const now = Date.now();
                if (now - lastEmit > 100) {
                    this.particleEngine.emit('stardust', e.clientX, e.clientY, 1);
                    lastEmit = now;
                }
            });
        }
    }
    
    startAmbientParticles() {
        if (!this.particleEngine) return;
        
        // Create background stardust
        setInterval(() => {
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            this.particleEngine.emit('stardust', x, y, 2);
        }, 500);
        
        // Create nebula gas clouds
        setInterval(() => {
            const x = Math.random() * window.innerWidth;
            const y = Math.random() * window.innerHeight;
            this.particleEngine.emit('nebula', x, y, 1);
        }, 2000);
    }
    
    showCommandPalette() {
        if (this.components.commandPalette) {
            this.components.commandPalette.show();
        }
    }
    
    hideCommandPalette() {
        if (this.components.commandPalette) {
            this.components.commandPalette.hide();
        }
    }
    
    showNotification(message, type = 'info') {
        if (this.components.notifications) {
            this.components.notifications.add(message, type);
        }
    }
    
    emitParticles(type, x, y, count = 10) {
        if (this.particleEngine) {
            this.particleEngine.emit(type, x, y, count);
        }
    }
    
    destroy() {
        if (this.particleEngine) {
            this.particleEngine.stop();
        }
        
        // Cleanup components
        Object.values(this.components).forEach(component => {
            if (component.destroy) {
                component.destroy();
            }
        });
    }
}

/**
 * Stellar Navigation Orb Component
 */
class StellarNavigationOrb {
    constructor(element, controller) {
        this.element = element;
        this.controller = controller;
        this.nodes = [];
        this.activeNode = null;
        this.rotation = { x: 0, y: 0 };
        
        this.init();
    }
    
    init() {
        this.parseNodes();
        this.setupInteractions();
        this.startRotation();
    }
    
    parseNodes() {
        const nodeElements = this.element.querySelectorAll('.kit-nebula-orb-node');
        this.nodes = Array.from(nodeElements);
    }
    
    setupInteractions() {
        this.nodes.forEach((node, index) => {
            node.addEventListener('click', () => this.selectNode(index));
            node.addEventListener('mouseenter', (e) => this.onNodeHover(node, e));
            node.addEventListener('mouseleave', (e) => this.onNodeLeave(node, e));
        });
        
        // Mouse-controlled rotation
        if (this.controller.threeDEffects) {
            this.element.addEventListener('mousemove', () => {
                this.controller.threeDEffects.applyMouseRotation(this.element, 5);
            });
        }
    }
    
    startRotation() {
        const rotate = () => {
            this.rotation.y += 0.1;
            this.element.style.transform = `rotateY(${this.rotation.y}deg)`;
            requestAnimationFrame(rotate);
        };
        
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            rotate();
        }
    }
    
    selectNode(index) {
        this.activeNode = index;
        this.nodes.forEach((node, i) => {
            if (i === index) {
                node.classList.add('is-active');
                this.controller.emitParticles('burst', 
                    node.offsetLeft + node.offsetWidth / 2,
                    node.offsetTop + node.offsetHeight / 2,
                    20
                );
            } else {
                node.classList.remove('is-active');
            }
        });
    }
    
    onNodeHover(node, event) {
        node.classList.add('is-hovered');
        if (this.controller.threeDEffects) {
            this.controller.threeDEffects.applyGravitationalLens(node, event);
        }
    }
    
    onNodeLeave(node, event) {
        node.classList.remove('is-hovered');
        if (this.controller.threeDEffects) {
            this.controller.threeDEffects.removeLens(node);
        }
    }
}

/**
 * Nebula Data Cards Component
 */
class NebulaDataCards {
    constructor(container, controller) {
        this.container = container;
        this.controller = controller;
        this.cards = [];
        
        this.init();
    }
    
    init() {
        this.parseCards();
        this.setupFloating();
        this.setupInteractions();
    }
    
    parseCards() {
        const cardElements = this.container.querySelectorAll('.kit-nebula-data-card');
        this.cards = Array.from(cardElements);
    }
    
    setupFloating() {
        this.cards.forEach((card, index) => {
            const delay = index * 0.5;
            card.style.animationDelay = `${delay}s`;
        });
    }
    
    setupInteractions() {
        this.cards.forEach(card => {
            card.addEventListener('click', () => this.expandCard(card));
            card.addEventListener('mouseenter', () => this.onCardHover(card));
            card.addEventListener('mouseleave', () => this.onCardLeave(card));
        });
    }
    
    onCardHover(card) {
        card.classList.add('is-hovered');
        this.controller.emitParticles('energy',
            card.offsetLeft + card.offsetWidth / 2,
            card.offsetTop + card.offsetHeight / 2,
            5
        );
    }
    
    onCardLeave(card) {
        card.classList.remove('is-hovered');
    }
    
    expandCard(card) {
        card.classList.toggle('is-expanded');
        this.controller.emitParticles('burst',
            card.offsetLeft + card.offsetWidth / 2,
            card.offsetTop + card.offsetHeight / 2,
            15
        );
    }
    
    updateData(cardIndex, data) {
        if (this.cards[cardIndex]) {
            const card = this.cards[cardIndex];
            const valueElement = card.querySelector('.kit-nebula-card-value');
            const trendElement = card.querySelector('.kit-nebula-card-trend');
            
            if (valueElement) {
                valueElement.textContent = data.value;
            }
            if (trendElement && data.trend) {
                trendElement.textContent = data.trend;
            }
            
            // Particle burst on update
            this.controller.emitParticles('energy',
                card.offsetLeft + card.offsetWidth / 2,
                card.offsetTop + card.offsetHeight / 2,
                10
            );
        }
    }
}

/**
 * Aurora Command Palette Component
 */
class AuroraCommandPalette {
    constructor(controller) {
        this.controller = controller;
        this.element = null;
        this.isVisible = false;
        this.commands = [];
        
        this.createPalette();
    }
    
    createPalette() {
        this.element = document.createElement('div');
        this.element.className = 'kit-nebula-command-palette';
        this.element.innerHTML = `
            <div class="kit-nebula-palette-overlay"></div>
            <div class="kit-nebula-palette-content">
                <input type="text" class="kit-nebula-palette-input" placeholder="Type a command..." />
                <div class="kit-nebula-palette-results"></div>
            </div>
        `;
        document.body.appendChild(this.element);
        
        this.setupInput();
    }
    
    setupInput() {
        const input = this.element.querySelector('.kit-nebula-palette-input');
        input.addEventListener('input', (e) => this.onInput(e));
        input.addEventListener('keydown', (e) => this.onKeydown(e));
    }
    
    show() {
        this.isVisible = true;
        this.element.classList.add('is-visible');
        const input = this.element.querySelector('.kit-nebula-palette-input');
        input.focus();
        
        // Aurora sweep effect
        this.createAuroraSweep();
    }
    
    hide() {
        this.isVisible = false;
        this.element.classList.remove('is-visible');
        const input = this.element.querySelector('.kit-nebula-palette-input');
        input.value = '';
    }
    
    createAuroraSweep() {
        const sweep = document.createElement('div');
        sweep.className = 'kit-nebula-aurora-sweep';
        document.body.appendChild(sweep);
        
        setTimeout(() => sweep.remove(), 1000);
    }
    
    onInput(e) {
        const query = e.target.value.toLowerCase();
        // Emit particle wake
        this.controller.emitParticles('energy', 
            window.innerWidth / 2, 
            window.innerHeight / 2, 
            3
        );
    }
    
    onKeydown(e) {
        if (e.key === 'Enter') {
            this.executeCommand();
        }
    }
    
    executeCommand() {
        const input = this.element.querySelector('.kit-nebula-palette-input');
        console.log('Execute command:', input.value);
        
        // Trigger aurora wave
        this.controller.emitParticles('burst',
            window.innerWidth / 2,
            window.innerHeight / 2,
            30
        );
        
        this.hide();
    }
}

/**
 * Stellar Notification Stream Component
 */
class StellarNotifications {
    constructor(container, controller) {
        this.container = container;
        this.controller = controller;
        this.notifications = [];
        
        this.init();
    }
    
    init() {
        this.container.style.position = 'fixed';
        this.container.style.top = '20px';
        this.container.style.right = '20px';
        this.container.style.zIndex = '9999';
    }
    
    add(message, type = 'info') {
        const notif = document.createElement('div');
        notif.className = `kit-nebula-notification kit-nebula-notification--${type}`;
        notif.innerHTML = `
            <div class="kit-nebula-notif-content">${message}</div>
            <button class="kit-nebula-notif-close">&times;</button>
        `;
        
        this.container.appendChild(notif);
        this.notifications.push(notif);
        
        // Meteor entry animation
        notif.style.animation = 'shooting-star 0.6s ease-out';
        
        // Setup close button
        const closeBtn = notif.querySelector('.kit-nebula-notif-close');
        closeBtn.addEventListener('click', () => this.remove(notif));
        
        // Auto-remove based on type
        const duration = type === 'critical' ? 0 : (type === 'warning' ? 6000 : 5000);
        if (duration > 0) {
            setTimeout(() => this.remove(notif), duration);
        }
        
        // Particle effect
        this.controller.emitParticles('meteor', 
            window.innerWidth - 200, 
            100, 
            5
        );
    }
    
    remove(notif) {
        notif.style.animation = 'fade-out 0.3s ease-out';
        setTimeout(() => {
            notif.remove();
            const index = this.notifications.indexOf(notif);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
        }, 300);
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.KitNebula = KitNebula;
    window.StellarNavigationOrb = StellarNavigationOrb;
    window.NebulaDataCards = NebulaDataCards;
    window.AuroraCommandPalette = AuroraCommandPalette;
    window.StellarNotifications = StellarNotifications;
}
