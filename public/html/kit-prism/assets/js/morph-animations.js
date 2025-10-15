/**
 * Kit Prism - Morphing Animations
 * Handles liquid morphing and shape transitions
 */

class MorphAnimations {
    constructor() {
        this.morphingElements = new Map();
        this.init();
    }
    
    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
    }
    
    /**
     * Morph element from one shape to another
     */
    morphShape(element, fromShape, toShape, duration = 600) {
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = this.easeInOutBack(progress);
            
            // Interpolate border radius
            const borderRadius = this.interpolateBorderRadius(fromShape, toShape, eased);
            element.style.borderRadius = borderRadius;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    /**
     * Create ripple effect at point
     */
    createRipple(element, x, y) {
        const ripple = document.createElement('div');
        ripple.className = 'prism-ripple-effect';
        ripple.style.cssText = `
            position: absolute;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(78, 205, 196, 0.6), transparent 70%);
            pointer-events: none;
            left: ${x}px;
            top: ${y}px;
            transform: translate(-50%, -50%);
        `;
        
        element.appendChild(ripple);
        
        const animation = ripple.animate([
            { transform: 'translate(-50%, -50%) scale(0)', opacity: 0.8 },
            { transform: 'translate(-50%, -50%) scale(20)', opacity: 0 }
        ], {
            duration: 800,
            easing: 'ease-out'
        });
        
        animation.onfinish = () => ripple.remove();
    }
    
    /**
     * Apply liquid flow effect
     */
    applyLiquidFlow(element, direction = 'horizontal') {
        const flow = document.createElement('div');
        flow.className = 'prism-liquid-flow';
        
        const gradient = direction === 'horizontal' 
            ? 'linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent)'
            : 'linear-gradient(180deg, transparent, rgba(255, 255, 255, 0.6), transparent)';
        
        flow.style.cssText = `
            position: absolute;
            inset: 0;
            background: ${gradient};
            pointer-events: none;
            animation: liquid-flow 3s ease-in-out infinite;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(flow);
        
        return flow;
    }
    
    /**
     * Blob morphing animation
     */
    startBlobMorph(element, options = {}) {
        const {
            duration = 12000,
            intensity = 1
        } = options;
        
        element.style.animation = `morph-shape-1 ${duration}ms ease-in-out infinite`;
        
        const id = Symbol('blob-morph');
        this.morphingElements.set(id, element);
        
        return () => {
            element.style.animation = '';
            this.morphingElements.delete(id);
        };
    }
    
    /**
     * Color morphing
     */
    morphColor(element, fromColor, toColor, duration = 800) {
        const startTime = performance.now();
        
        const parseColor = (color) => {
            const temp = document.createElement('div');
            temp.style.color = color;
            document.body.appendChild(temp);
            const computed = getComputedStyle(temp).color;
            document.body.removeChild(temp);
            
            const match = computed.match(/\d+/g);
            return match ? match.map(Number) : [0, 0, 0];
        };
        
        const from = parseColor(fromColor);
        const to = parseColor(toColor);
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = this.easeInOutCubic(progress);
            
            const r = Math.round(from[0] + (to[0] - from[0]) * eased);
            const g = Math.round(from[1] + (to[1] - from[1]) * eased);
            const b = Math.round(from[2] + (to[2] - from[2]) * eased);
            
            element.style.backgroundColor = `rgb(${r}, ${g}, ${b})`;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    /**
     * Scale with elastic bounce
     */
    elasticScale(element, targetScale = 1.1, duration = 600) {
        element.animate([
            { transform: 'scale(1)' },
            { transform: `scale(${targetScale})`, offset: 0.5 },
            { transform: 'scale(1)' }
        ], {
            duration,
            easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
        });
    }
    
    /**
     * Interpolate border radius values
     */
    interpolateBorderRadius(from, to, progress) {
        // Simple interpolation for demo - could be enhanced
        return `${Math.round(30 + progress * 30)}% ${Math.round(70 - progress * 30)}% ${Math.round(70 - progress * 40)}% ${Math.round(30 + progress * 40)}% / ${Math.round(60 - progress * 30)}% ${Math.round(30 + progress * 30)}% ${Math.round(70 - progress * 40)}% ${Math.round(40 + progress * 20)}%`;
    }
    
    /**
     * Easing functions
     */
    easeInOutCubic(t) {
        return t < 0.5
            ? 4 * t * t * t
            : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }
    
    easeInOutBack(t) {
        const c1 = 1.70158;
        const c2 = c1 * 1.525;
        
        return t < 0.5
            ? (Math.pow(2 * t, 2) * ((c2 + 1) * 2 * t - c2)) / 2
            : (Math.pow(2 * t - 2, 2) * ((c2 + 1) * (t * 2 - 2) + c2) + 2) / 2;
    }
    
    /**
     * Cleanup
     */
    destroy() {
        this.morphingElements.forEach(element => {
            element.style.animation = '';
        });
        this.morphingElements.clear();
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.MorphAnimations = MorphAnimations;
}
