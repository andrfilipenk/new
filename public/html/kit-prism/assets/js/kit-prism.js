/**
 * Kit Prism - Main Controller
 */

class KitPrism {
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.iridescence = null;
        this.morphAnimations = null;
        this.components = {};
        this.config = {
            enableIridescence: options.enableIridescence !== false,
            enableMorph: options.enableMorph !== false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Check for reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.config.enableIridescence = false;
            this.config.enableMorph = false;
        }
        
        // Initialize engines
        if (this.config.enableIridescence) {
            this.iridescence = new IridescenceEngine();
        }
        
        if (this.config.enableMorph) {
            this.morphAnimations = new MorphAnimations();
        }
        
        // Initialize components
        this.initializeComponents();
        this.setupEventListeners();
    }
    
    initializeComponents() {
        // Initialize panels
        const panels = document.querySelectorAll('.kit-prism-panel');
        panels.forEach(panel => {
            if (this.iridescence) {
                this.iridescence.register(panel, { type: 'holographic', intensity: 0.5 });
            }
        });
        
        // Initialize navigation
        const nav = document.querySelector('.kit-prism-nav');
        if (nav) {
            this.components.nav = new LiquidCrystalNav(nav, this);
        }
        
        // Initialize command center
        this.components.commandCenter = new SpectrumCommandCenter(this);
    }
    
    setupEventListeners() {
        // Add ripple effect to clickable elements
        document.querySelectorAll('.kit-prism-interactive').forEach(element => {
            element.addEventListener('click', (e) => {
                const rect = element.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                if (this.morphAnimations) {
                    this.morphAnimations.createRipple(element, x, y);
                }
            });
        });
        
        // Command center shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.showCommandCenter();
            }
        });
    }
    
    showCommandCenter() {
        if (this.components.commandCenter) {
            this.components.commandCenter.show();
        }
    }
    
    showNotification(message, type = 'info') {
        // Liquid wave notification
        const notification = document.createElement('div');
        notification.className = `kit-prism-notification kit-prism-notification--${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: fade-in-up 0.4s ease-out;
            z-index: 10000;
        `;
        
        document.body.appendChild(notification);
        
        if (this.iridescence) {
            this.iridescence.register(notification, { type: 'rainbow', speed: 2 });
        }
        
        setTimeout(() => {
            notification.style.animation = 'fade-out 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    destroy() {
        if (this.iridescence) {
            this.iridescence.destroy();
        }
        if (this.morphAnimations) {
            this.morphAnimations.destroy();
        }
    }
}

/**
 * Liquid Crystal Navigation
 */
class LiquidCrystalNav {
    constructor(element, controller) {
        this.element = element;
        this.controller = controller;
        this.items = [];
        
        this.init();
    }
    
    init() {
        this.items = Array.from(this.element.querySelectorAll('.kit-prism-nav-item'));
        this.setupInteractions();
    }
    
    setupInteractions() {
        this.items.forEach((item, index) => {
            item.addEventListener('mouseenter', () => this.onHover(item));
            item.addEventListener('mouseleave', () => this.onLeave(item));
            item.addEventListener('click', () => this.onSelect(item, index));
        });
    }
    
    onHover(item) {
        if (this.controller.morphAnimations) {
            this.controller.morphAnimations.elasticScale(item, 1.05, 300);
        }
    }
    
    onLeave(item) {
        // Animation handles return to normal
    }
    
    onSelect(item, index) {
        this.items.forEach(i => i.classList.remove('is-active'));
        item.classList.add('is-active');
        
        if (this.controller.morphAnimations) {
            this.controller.morphAnimations.createRipple(
                item,
                item.offsetWidth / 2,
                item.offsetHeight / 2
            );
        }
    }
}

/**
 * Spectrum Command Center
 */
class SpectrumCommandCenter {
    constructor(controller) {
        this.controller = controller;
        this.element = null;
        this.isVisible = false;
        
        this.createWheel();
    }
    
    createWheel() {
        this.element = document.createElement('div');
        this.element.className = 'kit-prism-command-wheel';
        this.element.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            width: 400px;
            height: 400px;
            margin: -200px 0 0 -200px;
            display: none;
            z-index: 10000;
        `;
        
        this.element.innerHTML = `
            <div class="wheel-overlay"></div>
            <div class="wheel-content">
                <input type="text" placeholder="Search commands..." class="wheel-input" />
            </div>
        `;
        
        document.body.appendChild(this.element);
    }
    
    show() {
        this.isVisible = true;
        this.element.style.display = 'block';
        this.element.style.animation = 'fade-in-scale 0.4s ease-out';
        
        const input = this.element.querySelector('.wheel-input');
        input.focus();
    }
    
    hide() {
        this.isVisible = false;
        this.element.style.animation = 'fade-out 0.3s ease-out';
        setTimeout(() => {
            this.element.style.display = 'none';
        }, 300);
    }
}

// Animation definitions (if not in CSS)
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fade-out {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes fade-in-scale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    `;
    document.head.appendChild(style);
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.KitPrism = KitPrism;
    window.LiquidCrystalNav = LiquidCrystalNav;
    window.SpectrumCommandCenter = SpectrumCommandCenter;
}
