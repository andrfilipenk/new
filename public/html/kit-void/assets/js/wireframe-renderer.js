/**
 * Kit Void - Wireframe Renderer
 * Creates and manipulates wireframe geometric shapes
 */

class WireframeRenderer {
    constructor(options = {}) {
        this.shapes = new Map();
        this.config = {
            vertexSize: options.vertexSize || 4,
            edgeWidth: options.edgeWidth || 2,
            defaultColor: options.defaultColor || '#00FFF0',
            ...options
        };
    }
    
    /**
     * Create a wireframe cube
     */
    createCube(container, size = 100, options = {}) {
        const cube = document.createElement('div');
        cube.className = 'kit-void-wireframe-cube';
        cube.style.width = `${size}px`;
        cube.style.height = `${size}px`;
        
        const faces = ['front', 'back', 'right', 'left', 'top', 'bottom'];
        faces.forEach(faceName => {
            const face = document.createElement('div');
            face.className = `face ${faceName}`;
            face.style.width = `${size}px`;
            face.style.height = `${size}px`;
            cube.appendChild(face);
        });
        
        if (container) {
            container.appendChild(cube);
        }
        
        const id = Symbol('cube');
        this.shapes.set(id, { element: cube, type: 'cube', options });
        
        return { element: cube, id };
    }
    
    /**
     * Create a wireframe sphere
     */
    createSphere(container, radius = 50, options = {}) {
        const sphere = document.createElement('div');
        sphere.className = 'kit-void-wireframe-sphere';
        sphere.style.width = `${radius * 2}px`;
        sphere.style.height = `${radius * 2}px`;
        
        if (container) {
            container.appendChild(sphere);
        }
        
        const id = Symbol('sphere');
        this.shapes.set(id, { element: sphere, type: 'sphere', options });
        
        return { element: sphere, id };
    }
    
    /**
     * Create vertices at corners
     */
    createVertices(container, positions) {
        const vertices = [];
        
        positions.forEach(({ x, y }) => {
            const vertex = document.createElement('div');
            vertex.className = 'kit-void-vertex';
            vertex.style.left = `${x}px`;
            vertex.style.top = `${y}px`;
            vertex.style.width = `${this.config.vertexSize}px`;
            vertex.style.height = `${this.config.vertexSize}px`;
            
            container.appendChild(vertex);
            vertices.push(vertex);
        });
        
        return vertices;
    }
    
    /**
     * Create edge between two points
     */
    createEdge(container, x1, y1, x2, y2) {
        const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
        const angle = Math.atan2(y2 - y1, x2 - x1) * (180 / Math.PI);
        
        const edge = document.createElement('div');
        edge.className = 'kit-void-edge';
        edge.style.width = `${length}px`;
        edge.style.left = `${x1}px`;
        edge.style.top = `${y1}px`;
        edge.style.transform = `rotate(${angle}deg)`;
        edge.style.height = `${this.config.edgeWidth}px`;
        
        container.appendChild(edge);
        
        return edge;
    }
    
    /**
     * Create wireframe box with vertices and edges
     */
    createWireframeBox(container, width, height) {
        const box = document.createElement('div');
        box.className = 'kit-void-data-container';
        box.style.width = `${width}px`;
        box.style.height = `${height}px`;
        box.style.position = 'relative';
        
        // Create corner vertices
        const vertices = [
            { x: -4, y: -4, class: 'vertex-tl' },
            { x: width - 4, y: -4, class: 'vertex-tr' },
            { x: -4, y: height - 4, class: 'vertex-bl' },
            { x: width - 4, y: height - 4, class: 'vertex-br' }
        ];
        
        vertices.forEach(({ x, y, class: className }) => {
            const vertex = document.createElement('div');
            vertex.className = `kit-void-vertex ${className}`;
            vertex.style.position = 'absolute';
            vertex.style.left = `${x}px`;
            vertex.style.top = `${y}px`;
            box.appendChild(vertex);
        });
        
        if (container) {
            container.appendChild(box);
        }
        
        return box;
    }
    
    /**
     * Animate shape rotation
     */
    animateRotation(shapeId, duration = 20000) {
        const shape = this.shapes.get(shapeId);
        if (!shape) return;
        
        let rotation = 0;
        const animate = () => {
            rotation += 360 / (duration / 16.67); // 60 FPS
            shape.element.style.transform = `rotateY(${rotation}deg) rotateX(${rotation * 0.5}deg)`;
            
            if (this.shapes.has(shapeId)) {
                requestAnimationFrame(animate);
            }
        };
        
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            animate();
        }
    }
    
    /**
     * Create isometric grid
     */
    createIsometricGrid(container, rows = 5, cols = 5) {
        const grid = document.createElement('div');
        grid.className = 'kit-void-isometric-grid';
        
        if (container) {
            container.appendChild(grid);
        }
        
        return grid;
    }
    
    /**
     * Destroy shape
     */
    destroy(shapeId) {
        const shape = this.shapes.get(shapeId);
        if (shape && shape.element) {
            shape.element.remove();
        }
        this.shapes.delete(shapeId);
    }
    
    /**
     * Cleanup all
     */
    destroyAll() {
        this.shapes.forEach(shape => {
            if (shape.element) {
                shape.element.remove();
            }
        });
        this.shapes.clear();
    }
}

// Auto-initialize
if (typeof module === 'undefined') {
    window.WireframeRenderer = WireframeRenderer;
}
