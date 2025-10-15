/**
 * Kit Void - Grid System
 * Manages infinite grid and scanline effects
 */

class GridSystem {
    constructor(options = {}) {
        this.config = {
            gridSize: options.gridSize || 50,
            scanlineSpeed: options.scanlineSpeed || 5000,
            enablePerspective: options.enablePerspective !== false,
            ...options
        };
        
        this.scanline = null;
        this.init();
    }
    
    init() {
        this.createGrid();
        this.createScanline();
    }
    
    createGrid() {
        // Check if grid already exists
        if (document.querySelector('.kit-void-grid')) return;
        
        const grid = document.createElement('div');
        grid.className = 'kit-void-grid';
        document.body.prepend(grid);
        
        return grid;
    }
    
    createScanline() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        
        this.scanline = document.createElement('div');
        this.scanline.className = 'kit-void-scanline';
        this.scanline.style.animationDuration = `${this.config.scanlineSpeed}ms`;
        document.body.appendChild(this.scanline);
    }
    
    /**
     * Create timeline grid
     */
    createTimelineGrid(container, width = 2000) {
        const timeline = document.createElement('div');
        timeline.className = 'kit-void-timeline';
        
        const grid = document.createElement('div');
        grid.className = 'kit-void-timeline-grid';
        grid.style.minWidth = `${width}px`;
        
        timeline.appendChild(grid);
        
        if (container) {
            container.appendChild(timeline);
        }
        
        return { timeline, grid };
    }
    
    /**
     * Add event to timeline
     */
    addTimelineEvent(grid, type, x, y) {
        const event = document.createElement('div');
        event.className = `kit-void-timeline-event event-${type}`;
        event.style.left = `${x}px`;
        event.style.top = `${y}px`;
        
        grid.appendChild(event);
        
        return event;
    }
    
    /**
     * Create command grid
     */
    createCommandGrid(container, cells = 9) {
        const grid = document.createElement('div');
        grid.className = 'kit-void-command-grid';
        
        const cols = Math.ceil(Math.sqrt(cells));
        grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        
        for (let i = 0; i < cells; i++) {
            const cell = document.createElement('div');
            cell.className = 'kit-void-grid-cell';
            cell.dataset.index = i;
            
            const label = document.createElement('div');
            label.className = 'kit-void-label';
            label.textContent = `Node ${i + 1}`;
            label.style.cssText = 'position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);';
            
            cell.appendChild(label);
            grid.appendChild(cell);
        }
        
        if (container) {
            container.appendChild(grid);
        }
        
        return grid;
    }
    
    /**
     * Apply perspective to grid
     */
    applyPerspective(element, angle = 60) {
        if (!this.config.enablePerspective) return;
        
        element.style.transform = `rotateX(${angle}deg)`;
        element.style.transformOrigin = 'center bottom';
    }
    
    /**
     * Animate grid scroll
     */
    animateGridScroll(grid, speed = 1) {
        let offset = 0;
        
        const animate = () => {
            offset += speed;
            grid.style.backgroundPosition = `${offset}px ${offset}px`;
            
            if (this.config.enablePerspective) {
                requestAnimationFrame(animate);
            }
        };
        
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            animate();
        }
    }
    
    /**
     * Cleanup
     */
    destroy() {
        if (this.scanline) {
            this.scanline.remove();
        }
        
        const grid = document.querySelector('.kit-void-grid');
        if (grid) {
            grid.remove();
        }
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.GridSystem = GridSystem;
}
