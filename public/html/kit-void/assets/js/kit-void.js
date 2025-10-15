/**
 * Kit Void - Main Controller
 */

class KitVoid {
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.wireframeRenderer = null;
        this.gridSystem = null;
        this.components = {};
        this.config = {
            enableWireframes: options.enableWireframes !== false,
            enableGrid: options.enableGrid !== false,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Check for reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.config.enableWireframes = false;
        }
        
        // Initialize systems
        if (this.config.enableWireframes) {
            this.wireframeRenderer = new WireframeRenderer();
        }
        
        if (this.config.enableGrid) {
            this.gridSystem = new GridSystem();
        }
        
        // Initialize components
        this.initializeComponents();
        this.setupEventListeners();
    }
    
    initializeComponents() {
        // Initialize command grid
        const commandGrid = document.querySelector('.kit-void-command-grid');
        if (commandGrid) {
            this.components.commandGrid = new IsometricCommandGrid(commandGrid, this);
        }
        
        // Initialize modal system
        this.components.modal = new GeometricModal(this);
        
        // Initialize alert system
        this.components.alerts = new VoidAlertSystem(this);
    }
    
    setupEventListeners() {
        // Grid cell interactions
        document.querySelectorAll('.kit-void-grid-cell').forEach(cell => {
            cell.addEventListener('click', () => {
                this.selectCell(cell);
            });
        });
        
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.components.modal) {
                this.components.modal.hide();
            }
        });
    }
    
    selectCell(cell) {
        document.querySelectorAll('.kit-void-grid-cell').forEach(c => {
            c.classList.remove('is-active');
        });
        cell.classList.add('is-active');
    }
    
    showModal(content, title = '') {
        if (this.components.modal) {
            this.components.modal.show(content, title);
        }
    }
    
    showAlert(message, type = 'info') {
        if (this.components.alerts) {
            this.components.alerts.add(message, type);
        }
    }
    
    destroy() {
        if (this.wireframeRenderer) {
            this.wireframeRenderer.destroyAll();
        }
        if (this.gridSystem) {
            this.gridSystem.destroy();
        }
    }
}

/**
 * Isometric Command Grid Component
 */
class IsometricCommandGrid {
    constructor(element, controller) {
        this.element = element;
        this.controller = controller;
        this.cells = [];
        this.activeCell = null;
        
        this.init();
    }
    
    init() {
        this.cells = Array.from(this.element.querySelectorAll('.kit-void-grid-cell'));
        this.setupInteractions();
    }
    
    setupInteractions() {
        this.cells.forEach((cell, index) => {
            cell.addEventListener('mouseenter', () => this.onHover(cell));
            cell.addEventListener('mouseleave', () => this.onLeave(cell));
            cell.addEventListener('click', () => this.onSelect(cell, index));
        });
    }
    
    onHover(cell) {
        if (!cell.classList.contains('is-active')) {
            cell.style.transform = 'translateZ(10px)';
        }
    }
    
    onLeave(cell) {
        if (!cell.classList.contains('is-active')) {
            cell.style.transform = '';
        }
    }
    
    onSelect(cell, index) {
        this.activeCell = index;
        this.cells.forEach(c => c.classList.remove('is-active'));
        cell.classList.add('is-active');
    }
}

/**
 * Geometric Modal System
 */
class GeometricModal {
    constructor(controller) {
        this.controller = controller;
        this.element = null;
        this.isVisible = false;
        
        this.createModal();
    }
    
    createModal() {
        this.element = document.createElement('div');
        this.element.className = 'kit-void-modal';
        
        this.element.innerHTML = `
            <div class="kit-void-modal-overlay"></div>
            <div class="kit-void-modal-content">
                <div class="modal-header">
                    <h2 class="kit-void-h2 modal-title"></h2>
                    <button class="modal-close" style="position: absolute; top: 1rem; right: 1rem; background: none; border: 2px solid var(--void-cyan); color: var(--void-cyan); padding: 0.5rem 1rem; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="margin-top: 1.5rem;"></div>
            </div>
        `;
        
        document.body.appendChild(this.element);
        
        // Close button
        this.element.querySelector('.modal-close').addEventListener('click', () => this.hide());
        
        // Click overlay to close
        this.element.querySelector('.kit-void-modal-overlay').addEventListener('click', () => this.hide());
    }
    
    show(content, title = '') {
        this.isVisible = true;
        this.element.classList.add('is-visible');
        
        this.element.querySelector('.modal-title').textContent = title;
        this.element.querySelector('.modal-body').innerHTML = content;
    }
    
    hide() {
        this.isVisible = false;
        this.element.classList.remove('is-visible');
    }
}

/**
 * Void Alert System
 */
class VoidAlertSystem {
    constructor(controller) {
        this.controller = controller;
        this.container = null;
        this.alerts = [];
        
        this.createContainer();
    }
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'kit-void-alerts';
        document.body.appendChild(this.container);
    }
    
    add(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `kit-void-alert kit-void-alert--${type}`;
        
        alert.innerHTML = `
            <div class="kit-void-body">${message}</div>
            <button class="alert-close" style="position: absolute; top: 0.5rem; right: 0.5rem; background: none; border: none; color: var(--void-white); cursor: pointer; font-size: 1.5rem;">&times;</button>
        `;
        
        this.container.appendChild(alert);
        this.alerts.push(alert);
        
        // Close button
        alert.querySelector('.alert-close').addEventListener('click', () => this.remove(alert));
        
        // Auto-remove (except critical)
        if (type !== 'critical') {
            setTimeout(() => this.remove(alert), 5000);
        }
    }
    
    remove(alert) {
        alert.style.animation = 'fade-out 0.3s ease-out';
        setTimeout(() => {
            alert.remove();
            const index = this.alerts.indexOf(alert);
            if (index > -1) {
                this.alerts.splice(index, 1);
            }
        }, 300);
    }
}

// Animation helper
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fade-out {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(100px); }
        }
    `;
    document.head.appendChild(style);
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.KitVoid = KitVoid;
    window.IsometricCommandGrid = IsometricCommandGrid;
    window.GeometricModal = GeometricModal;
    window.VoidAlertSystem = VoidAlertSystem;
}
