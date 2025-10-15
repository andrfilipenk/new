/**
 * Kit Nebula - Particle Engine
 * High-performance particle system for cosmic effects
 */

class ParticleEngine {
    constructor(options = {}) {
        this.canvas = options.canvas || this.createCanvas();
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.maxParticles = options.maxParticles || 500;
        this.particlePool = [];
        this.isRunning = false;
        this.lastFrame = 0;
        
        this.init();
    }
    
    createCanvas() {
        const canvas = document.createElement('canvas');
        canvas.className = 'kit-nebula-particles';
        document.body.appendChild(canvas);
        return canvas;
    }
    
    init() {
        this.resize();
        window.addEventListener('resize', () => this.resize());
        
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        
        this.start();
    }
    
    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }
    
    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        this.animate();
    }
    
    stop() {
        this.isRunning = false;
    }
    
    animate(timestamp = 0) {
        if (!this.isRunning) return;
        
        const deltaTime = timestamp - this.lastFrame;
        this.lastFrame = timestamp;
        
        this.update(deltaTime);
        this.render();
        
        requestAnimationFrame((t) => this.animate(t));
    }
    
    update(deltaTime) {
        // Update existing particles
        for (let i = this.particles.length - 1; i >= 0; i--) {
            const particle = this.particles[i];
            particle.update(deltaTime);
            
            // Remove dead particles
            if (particle.isDead()) {
                this.recycleParticle(particle);
                this.particles.splice(i, 1);
            }
        }
    }
    
    render() {
        // Clear canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Render particles
        for (const particle of this.particles) {
            particle.render(this.ctx);
        }
    }
    
    createParticle(type, x, y, options = {}) {
        if (this.particles.length >= this.maxParticles) {
            return null;
        }
        
        let particle;
        
        // Try to reuse particle from pool
        if (this.particlePool.length > 0) {
            particle = this.particlePool.pop();
            particle.reset(type, x, y, options);
        } else {
            particle = new Particle(type, x, y, options);
        }
        
        this.particles.push(particle);
        return particle;
    }
    
    recycleParticle(particle) {
        if (this.particlePool.length < 100) {
            this.particlePool.push(particle);
        }
    }
    
    emit(type, x, y, count = 1, options = {}) {
        for (let i = 0; i < count; i++) {
            this.createParticle(type, x, y, options);
        }
    }
    
    clear() {
        this.particles = [];
    }
}

class Particle {
    constructor(type, x, y, options = {}) {
        this.reset(type, x, y, options);
    }
    
    reset(type, x, y, options = {}) {
        this.type = type;
        this.x = x;
        this.y = y;
        this.vx = options.vx || 0;
        this.vy = options.vy || 0;
        this.size = options.size || 2;
        this.color = options.color || '#FFFFFF';
        this.alpha = options.alpha !== undefined ? options.alpha : 1;
        this.lifetime = options.lifetime || 3000;
        this.age = 0;
        this.gravity = options.gravity || 0;
        this.friction = options.friction || 1;
        
        this.initTypeSpecific();
    }
    
    initTypeSpecific() {
        switch (this.type) {
            case 'stardust':
                this.size = 1 + Math.random();
                this.lifetime = 3000 + Math.random() * 2000;
                this.vx = (Math.random() - 0.5) * 0.1;
                this.vy = (Math.random() - 0.5) * 0.1;
                break;
                
            case 'nebula':
                this.size = 8 + Math.random() * 8;
                this.lifetime = 10000 + Math.random() * 5000;
                this.color = Math.random() > 0.5 ? '#7209B7' : '#00F5FF';
                this.alpha = 0.3;
                break;
                
            case 'energy':
                this.size = 2 + Math.random() * 2;
                this.lifetime = 1000 + Math.random() * 1000;
                this.color = '#00F5FF';
                this.vy = 0.5 + Math.random() * 0.5;
                this.vx = (Math.random() - 0.5) * 0.2;
                break;
                
            case 'meteor':
                this.size = 3 + Math.random() * 3;
                this.lifetime = 500 + Math.random() * 300;
                this.color = '#FFBA08';
                this.vx = 1 + Math.random();
                this.vy = 1 + Math.random();
                break;
                
            case 'burst':
                this.size = 4 + Math.random() * 4;
                this.lifetime = 2000 + Math.random() * 1000;
                this.color = '#F72585';
                const angle = Math.random() * Math.PI * 2;
                const speed = 0.5 + Math.random() * 0.5;
                this.vx = Math.cos(angle) * speed;
                this.vy = Math.sin(angle) * speed;
                break;
        }
    }
    
    update(deltaTime) {
        this.age += deltaTime;
        
        // Update position
        this.x += this.vx;
        this.y += this.vy;
        
        // Apply gravity
        this.vy += this.gravity;
        
        // Apply friction
        this.vx *= this.friction;
        this.vy *= this.friction;
        
        // Update alpha based on lifetime
        const lifePercent = this.age / this.lifetime;
        if (lifePercent > 0.7) {
            this.alpha = (1 - lifePercent) / 0.3;
        }
    }
    
    render(ctx) {
        ctx.save();
        ctx.globalAlpha = this.alpha;
        
        // Render based on type
        if (this.type === 'nebula') {
            const gradient = ctx.createRadialGradient(
                this.x, this.y, 0,
                this.x, this.y, this.size
            );
            gradient.addColorStop(0, this.color);
            gradient.addColorStop(1, 'transparent');
            
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        } else {
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
            
            // Add glow for certain types
            if (this.type === 'energy' || this.type === 'meteor' || this.type === 'burst') {
                ctx.shadowBlur = 10;
                ctx.shadowColor = this.color;
                ctx.fill();
            }
        }
        
        ctx.restore();
    }
    
    isDead() {
        return this.age >= this.lifetime || 
               this.x < -50 || this.x > window.innerWidth + 50 ||
               this.y < -50 || this.y > window.innerHeight + 50;
    }
}

// Auto-initialize if not using modules
if (typeof module === 'undefined') {
    window.ParticleEngine = ParticleEngine;
    window.Particle = Particle;
}
