/**
 * Kit Prism - Iridescence Engine
 * Creates rainbow shimmer and holographic effects
 */

class IridescenceEngine {
    constructor(options = {}) {
        this.elements = [];
        this.mousePosition = { x: 0, y: 0 };
        this.isActive = true;
        
        this.init();
    }
    
    init() {
        // Check for reduced motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.isActive = false;
            return;
        }
        
        this.setupMouseTracking();
        this.animate();
    }
    
    setupMouseTracking() {
        document.addEventListener('mousemove', (e) => {
            this.mousePosition.x = e.clientX / window.innerWidth;
            this.mousePosition.y = e.clientY / window.innerHeight;
        });
    }
    
    register(element, options = {}) {
        if (!this.isActive) return;
        
        const config = {
            type: options.type || 'shimmer',
            intensity: options.intensity || 1,
            speed: options.speed || 1,
            ...options
        };
        
        this.elements.push({ element, config });
        this.applyEffect(element, config);
    }
    
    applyEffect(element, config) {
        switch (config.type) {
            case 'shimmer':
                this.applyShimmer(element, config);
                break;
            case 'holographic':
                this.applyHolographic(element, config);
                break;
            case 'rainbow':
                this.applyRainbow(element, config);
                break;
            case 'chromatic':
                this.applyChromatic(element, config);
                break;
        }
    }
    
    applyShimmer(element, config) {
        element.style.background = `
            linear-gradient(90deg, 
                rgba(78, 205, 196, 0.3), 
                rgba(255, 107, 157, 0.3), 
                rgba(255, 209, 102, 0.3),
                rgba(78, 205, 196, 0.3)
            )
        `;
        element.style.backgroundSize = '300% 300%';
        element.style.animation = `shimmer-iridescent ${4 / config.speed}s ease-in-out infinite`;
    }
    
    applyHolographic(element, config) {
        const overlay = document.createElement('div');
        overlay.className = 'iridescence-overlay';
        overlay.style.cssText = `
            position: absolute;
            inset: 0;
            background: conic-gradient(
                from 0deg,
                transparent,
                rgba(78, 205, 196, ${0.3 * config.intensity}),
                rgba(255, 107, 157, ${0.3 * config.intensity}),
                rgba(255, 209, 102, ${0.3 * config.intensity}),
                transparent
            );
            pointer-events: none;
            animation: rotate-slow ${10 / config.speed}s linear infinite;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(overlay);
    }
    
    applyRainbow(element, config) {
        const shimmer = document.createElement('div');
        shimmer.className = 'iridescence-shimmer';
        shimmer.style.cssText = `
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, ${0.6 * config.intensity}),
                transparent
            );
            pointer-events: none;
            animation: liquid-flow ${3 / config.speed}s ease-in-out infinite;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(shimmer);
    }
    
    applyChromatic(element, config) {
        const text = element.textContent;
        element.setAttribute('data-text', text);
        element.classList.add('kit-prism-chromatic');
    }
    
    updateMouseEffect(element, config) {
        if (!this.isActive) return;
        
        const rect = element.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        
        const deltaX = (this.mousePosition.x * window.innerWidth - centerX) / rect.width;
        const deltaY = (this.mousePosition.y * window.innerHeight - centerY) / rect.height;
        
        const angle = Math.atan2(deltaY, deltaX) * (180 / Math.PI);
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
        const influence = Math.max(0, 1 - distance);
        
        if (config.type === 'holographic') {
            const overlay = element.querySelector('.iridescence-overlay');
            if (overlay) {
                overlay.style.transform = `rotate(${angle}deg)`;
                overlay.style.opacity = 0.3 + (influence * 0.4);
            }
        }
    }
    
    animate() {
        if (!this.isActive) return;
        
        this.elements.forEach(({ element, config }) => {
            this.updateMouseEffect(element, config);
        });
        
        requestAnimationFrame(() => this.animate());
    }
    
    createSpectrum(hueStart = 0, hueEnd = 360, steps = 10) {
        const colors = [];
        const hueStep = (hueEnd - hueStart) / steps;
        
        for (let i = 0; i < steps; i++) {
            const hue = hueStart + (hueStep * i);
            colors.push(`hsl(${hue}, 70%, 60%)`);
        }
        
        return colors;
    }
    
    destroy() {
        this.isActive = false;
        this.elements = [];
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.IridescenceEngine = IridescenceEngine;
}
