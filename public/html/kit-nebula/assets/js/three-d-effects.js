/**
 * Kit Nebula - 3D Transform Effects
 * Handles 3D transformations and perspective effects
 */

class ThreeDEffects {
    constructor() {
        this.mouse = { x: 0, y: 0 };
        this.targetRotation = { x: 0, y: 0 };
        this.currentRotation = { x: 0, y: 0 };
        this.momentum = { x: 0, y: 0 };
        this.isDragging = false;
        this.lastMouse = { x: 0, y: 0 };
        
        this.init();
    }
    
    init() {
        this.setupMouseTracking();
        this.animate();
    }
    
    setupMouseTracking() {
        document.addEventListener('mousemove', (e) => {
            this.mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
            this.mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;
        });
        
        document.addEventListener('mousedown', (e) => {
            this.isDragging = true;
            this.lastMouse = { x: e.clientX, y: e.clientY };
        });
        
        document.addEventListener('mouseup', () => {
            this.isDragging = false;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (this.isDragging) {
                const deltaX = e.clientX - this.lastMouse.x;
                const deltaY = e.clientY - this.lastMouse.y;
                
                this.momentum.x = deltaX * 0.5;
                this.momentum.y = deltaY * 0.5;
                
                this.lastMouse = { x: e.clientX, y: e.clientY };
            }
        });
    }
    
    animate() {
        // Smooth rotation interpolation
        this.currentRotation.x += (this.targetRotation.x - this.currentRotation.x) * 0.1;
        this.currentRotation.y += (this.targetRotation.y - this.currentRotation.y) * 0.1;
        
        // Apply momentum
        if (!this.isDragging) {
            this.targetRotation.x += this.momentum.y;
            this.targetRotation.y += this.momentum.x;
            
            // Friction
            this.momentum.x *= 0.95;
            this.momentum.y *= 0.95;
        }
        
        requestAnimationFrame(() => this.animate());
    }
    
    /**
     * Apply mouse-based rotation to element
     */
    applyMouseRotation(element, intensity = 10) {
        if (!element) return;
        
        const rotateX = this.mouse.y * intensity;
        const rotateY = this.mouse.x * intensity;
        
        element.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    }
    
    /**
     * Apply drag-based rotation to element
     */
    applyDragRotation(element) {
        if (!element) return;
        
        element.style.transform = `rotateX(${this.currentRotation.x}deg) rotateY(${this.currentRotation.y}deg)`;
    }
    
    /**
     * Create parallax effect based on mouse position
     */
    applyParallax(element, depth = 20) {
        if (!element) return;
        
        const x = this.mouse.x * depth;
        const y = this.mouse.y * depth;
        
        element.style.transform = `translate(${x}px, ${y}px)`;
    }
    
    /**
     * Apply gravitational lens distortion effect
     */
    applyGravitationalLens(element, event) {
        if (!element) return;
        
        const rect = element.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        
        const deltaX = event.clientX - centerX;
        const deltaY = event.clientY - centerY;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
        
        const maxDistance = Math.max(rect.width, rect.height) / 2;
        const influence = Math.max(0, 1 - distance / maxDistance);
        
        const scale = 1 + influence * 0.1;
        const blur = influence * 3;
        
        element.style.transform = `scale(${scale})`;
        element.style.filter = `blur(${blur}px)`;
    }
    
    /**
     * Remove lens distortion
     */
    removeLens(element) {
        if (!element) return;
        
        element.style.transform = '';
        element.style.filter = '';
    }
    
    /**
     * Create 3D depth layers
     */
    createDepthLayers(container, layers = 5) {
        if (!container) return;
        
        const elements = Array.from(container.children);
        const layerSize = Math.ceil(elements.length / layers);
        
        elements.forEach((element, index) => {
            const layer = Math.floor(index / layerSize);
            const zOffset = -layer * 100;
            
            element.style.transform = `translateZ(${zOffset}px)`;
            element.style.opacity = 1 - (layer * 0.1);
        });
    }
    
    /**
     * Rotate element on specific axis
     */
    rotateOnAxis(element, axis, degrees, duration = 400) {
        if (!element) return;
        
        element.style.transition = `transform ${duration}ms ease-in-out`;
        
        switch (axis) {
            case 'x':
                element.style.transform = `rotateX(${degrees}deg)`;
                break;
            case 'y':
                element.style.transform = `rotateY(${degrees}deg)`;
                break;
            case 'z':
                element.style.transform = `rotateZ(${degrees}deg)`;
                break;
        }
    }
    
    /**
     * Create orbital rotation effect
     */
    createOrbit(element, radius = 100, speed = 0.01) {
        let angle = 0;
        
        const animate = () => {
            angle += speed;
            const x = Math.cos(angle) * radius;
            const z = Math.sin(angle) * radius;
            
            element.style.transform = `translateX(${x}px) translateZ(${z}px)`;
            
            requestAnimationFrame(animate);
        };
        
        animate();
    }
    
    /**
     * Flip card effect
     */
    flipCard(element, direction = 'horizontal') {
        if (!element) return;
        
        const rotateAxis = direction === 'horizontal' ? 'Y' : 'X';
        const currentRotation = element.dataset.rotation || 0;
        const newRotation = (parseInt(currentRotation) + 180) % 360;
        
        element.style.transition = 'transform 0.6s';
        element.style.transform = `rotate${rotateAxis}(${newRotation}deg)`;
        element.dataset.rotation = newRotation;
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.ThreeDEffects = ThreeDEffects;
}
