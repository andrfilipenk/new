/**
 * Kit Meridian - Spatial Computing Workspace
 * JavaScript for 3D Interactions
 */

class SpatialWorkspace {
    constructor() {
        this.workspace = null;
        this.panels = [];
        this.draggedPanel = null;
        this.isDragging = false;
        this.rotationX = 0;
        this.rotationY = 0;
        this.zoom = 1;
        this.mouseStartX = 0;
        this.mouseStartY = 0;
        
        this.init();
    }
    
    init() {
        this.workspace = document.querySelector('.spatial-workspace');
        if (!this.workspace) {
            this.createWorkspace();
        }
        
        this.initSpatialPanels();
        this.initDataCubes();
        this.initTaskPipeline();
        this.initSpatialControls();
        this.initMinimap();
        this.initCollaboration();
        this.setupGestureTracking();
        this.setupKeyboardShortcuts();
        
        console.log('🌌 Spatial Workspace initialized');
    }
    
    /* ===================================
       WORKSPACE MANAGEMENT
       =================================== */
    createWorkspace() {
        const workspace = document.createElement('div');
        workspace.className = 'spatial-workspace';
        document.body.appendChild(workspace);
        this.workspace = workspace;
    }
    
    /* ===================================
       SPATIAL PANELS
       =================================== */
    initSpatialPanels() {
        const panels = document.querySelectorAll('.spatial-panel');
        
        panels.forEach(panel => {
            this.makePanelDraggable(panel);
            this.panels.push(panel);
        });
    }
    
    makePanelDraggable(panel) {
        let isDragging = false;
        let currentX = 0;
        let currentY = 0;
        let initialX = 0;
        let initialY = 0;
        let xOffset = 0;
        let yOffset = 0;
        
        panel.addEventListener('mousedown', (e) => {
            if (e.target.closest('.panel-btn')) return;
            
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
            isDragging = true;
            panel.style.cursor = 'grabbing';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            e.preventDefault();
            currentX = e.clientX - initialX;
            currentY = e.clientY - initialY;
            xOffset = currentX;
            yOffset = currentY;
            
            this.setTranslate(currentX, currentY, panel);
        });
        
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                panel.style.cursor = 'grab';
            }
        });
        
        // Panel control buttons
        const closeBtn = panel.querySelector('[data-action="close"]');
        const minimizeBtn = panel.querySelector('[data-action="minimize"]');
        const expandBtn = panel.querySelector('[data-action="expand"]');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closePanel(panel));
        }
        
        if (minimizeBtn) {
            minimizeBtn.addEventListener('click', () => this.minimizePanel(panel));
        }
        
        if (expandBtn) {
            expandBtn.addEventListener('click', () => this.expandPanel(panel));
        }
    }
    
    setTranslate(xPos, yPos, el) {
        const currentTransform = el.style.transform || '';
        const zMatch = currentTransform.match(/translateZ\(([^)]+)\)/);
        const zValue = zMatch ? zMatch[1] : '0px';
        
        el.style.transform = `translate3d(${xPos}px, ${yPos}px, ${zValue})`;
    }
    
    closePanel(panel) {
        panel.classList.add('spatial-hidden');
        setTimeout(() => {
            panel.remove();
            const index = this.panels.indexOf(panel);
            if (index > -1) this.panels.splice(index, 1);
        }, 400);
    }
    
    minimizePanel(panel) {
        panel.classList.toggle('panel-minimized');
        const content = panel.querySelector('.panel-content');
        if (content) {
            content.style.display = panel.classList.contains('panel-minimized') ? 'none' : 'block';
        }
    }
    
    expandPanel(panel) {
        panel.classList.toggle('panel-expanded');
    }
    
    /* ===================================
       DATA CUBES
       =================================== */
    initDataCubes() {
        const cubes = document.querySelectorAll('.data-cube');
        
        cubes.forEach(cube => {
            this.makesCubeRotatable(cube);
            
            // Make cube faces interactive
            const faces = cube.querySelectorAll('.cube-face');
            faces.forEach(face => {
                face.addEventListener('click', () => {
                    this.focusCubeFace(cube, face);
                });
            });
        });
    }
    
    makesCubeRotatable(cube) {
        let isDragging = false;
        let previousMouseX = 0;
        let previousMouseY = 0;
        let rotationX = -15;
        let rotationY = 25;
        
        cube.addEventListener('mousedown', (e) => {
            isDragging = true;
            previousMouseX = e.clientX;
            previousMouseY = e.clientY;
            cube.style.cursor = 'grabbing';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const deltaX = e.clientX - previousMouseX;
            const deltaY = e.clientY - previousMouseY;
            
            rotationY += deltaX * 0.5;
            rotationX -= deltaY * 0.5;
            
            cube.style.transform = `rotateX(${rotationX}deg) rotateY(${rotationY}deg)`;
            
            previousMouseX = e.clientX;
            previousMouseY = e.clientY;
        });
        
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                cube.style.cursor = 'grab';
            }
        });
    }
    
    focusCubeFace(cube, face) {
        // Rotate cube to focus on clicked face
        const faceClass = Array.from(face.classList).find(cls => cls.startsWith('cube-face-'));
        
        const rotations = {
            'cube-face-front': { x: 0, y: 0 },
            'cube-face-back': { x: 0, y: 180 },
            'cube-face-left': { x: 0, y: -90 },
            'cube-face-right': { x: 0, y: 90 },
            'cube-face-top': { x: -90, y: 0 },
            'cube-face-bottom': { x: 90, y: 0 }
        };
        
        if (rotations[faceClass]) {
            cube.style.transition = 'transform 0.6s ease-in-out';
            cube.style.transform = `rotateX(${rotations[faceClass].x}deg) rotateY(${rotations[faceClass].y}deg)`;
            
            setTimeout(() => {
                cube.style.transition = '';
            }, 600);
        }
    }
    
    /* ===================================
       TASK PIPELINE
       =================================== */
    initTaskPipeline() {
        const tasks = document.querySelectorAll('.task-object');
        
        tasks.forEach(task => {
            this.makeTaskDraggable(task);
        });
        
        // Setup drop zones
        const zones = document.querySelectorAll('.workflow-zone');
        zones.forEach(zone => {
            this.setupDropZone(zone);
        });
    }
    
    makeTaskDraggable(task) {
        let isDragging = false;
        let currentX = 0;
        let currentY = 0;
        let initialX = 0;
        let initialY = 0;
        
        task.addEventListener('mousedown', (e) => {
            isDragging = true;
            initialX = e.clientX - currentX;
            initialY = e.clientY - currentY;
            task.style.zIndex = '1000';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            e.preventDefault();
            currentX = e.clientX - initialX;
            currentY = e.clientY - initialY;
            
            task.style.left = `${currentX}px`;
            task.style.top = `${currentY}px`;
        });
        
        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                this.checkTaskDrop(task);
                task.style.zIndex = '';
            }
        });
    }
    
    setupDropZone(zone) {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('zone-hover');
        });
        
        zone.addEventListener('dragleave', () => {
            zone.classList.remove('zone-hover');
        });
    }
    
    checkTaskDrop(task) {
        const zones = document.querySelectorAll('.workflow-zone');
        const taskRect = task.getBoundingClientRect();
        const taskCenterX = taskRect.left + taskRect.width / 2;
        const taskCenterY = taskRect.top + taskRect.height / 2;
        
        zones.forEach(zone => {
            const zoneRect = zone.getBoundingClientRect();
            
            if (taskCenterX >= zoneRect.left && taskCenterX <= zoneRect.right &&
                taskCenterY >= zoneRect.top && taskCenterY <= zoneRect.bottom) {
                
                zone.appendChild(task);
                task.style.left = '10px';
                task.style.top = '50px';
            }
        });
    }
    
    /* ===================================
       SPATIAL CONTROLS
       =================================== */
    initSpatialControls() {
        const controls = document.querySelector('.spatial-controls');
        if (!controls) this.createSpatialControls();
        
        // Navigation buttons
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.control-btn');
            if (!btn) return;
            
            const action = btn.getAttribute('data-action');
            this.handleControlAction(action);
        });
    }
    
    createSpatialControls() {
        const controls = document.createElement('div');
        controls.className = 'spatial-controls';
        controls.innerHTML = `
            <button class="control-btn" data-action="zoom-in" title="Zoom In">
                <span>+</span>
            </button>
            <button class="control-btn" data-action="reset" title="Reset View">
                <span>⊙</span>
            </button>
            <button class="control-btn" data-action="zoom-out" title="Zoom Out">
                <span>−</span>
            </button>
            <button class="control-btn" data-action="layer-up" title="Move Layer Up">
                <span>↑</span>
            </button>
            <button class="control-btn" data-action="layer-down" title="Move Layer Down">
                <span>↓</span>
            </button>
        `;
        document.body.appendChild(controls);
    }
    
    handleControlAction(action) {
        switch(action) {
            case 'zoom-in':
                this.zoom = Math.min(this.zoom + 0.1, 2);
                this.updateWorkspaceTransform();
                break;
            case 'zoom-out':
                this.zoom = Math.max(this.zoom - 0.1, 0.5);
                this.updateWorkspaceTransform();
                break;
            case 'reset':
                this.rotationX = 0;
                this.rotationY = 0;
                this.zoom = 1;
                this.updateWorkspaceTransform();
                break;
            case 'layer-up':
                this.rotationX += 15;
                this.updateWorkspaceTransform();
                break;
            case 'layer-down':
                this.rotationX -= 15;
                this.updateWorkspaceTransform();
                break;
        }
    }
    
    updateWorkspaceTransform() {
        if (!this.workspace) return;
        
        this.workspace.style.transform = `
            rotateX(${this.rotationX}deg) 
            rotateY(${this.rotationY}deg) 
            scale(${this.zoom})
        `;
    }
    
    /* ===================================
       MINIMAP
       =================================== */
    initMinimap() {
        const minimap = document.querySelector('.spatial-minimap');
        if (!minimap) return;
        
        this.updateMinimap();
        
        // Update minimap periodically
        setInterval(() => {
            this.updateMinimap();
        }, 1000);
    }
    
    updateMinimap() {
        const minimapView = document.querySelector('.minimap-view');
        if (!minimapView) return;
        
        // Clear existing objects
        const existingObjects = minimapView.querySelectorAll('.minimap-object');
        existingObjects.forEach(obj => obj.remove());
        
        // Add panel representations
        this.panels.forEach(panel => {
            const rect = panel.getBoundingClientRect();
            const minimapObject = document.createElement('div');
            minimapObject.className = 'minimap-object';
            
            // Scale position to minimap
            const scaleX = minimapView.offsetWidth / window.innerWidth;
            const scaleY = minimapView.offsetHeight / window.innerHeight;
            
            minimapObject.style.left = `${rect.left * scaleX}px`;
            minimapObject.style.top = `${rect.top * scaleY}px`;
            
            minimapView.appendChild(minimapObject);
        });
    }
    
    /* ===================================
       COLLABORATION
       =================================== */
    initCollaboration() {
        // Simulate user avatars
        this.createUserAvatars();
    }
    
    createUserAvatars() {
        const users = [
            { id: 1, name: 'Sarah Chen', initials: 'SC', status: 'online', x: 200, y: 150 },
            { id: 2, name: 'Mike Torres', initials: 'MT', status: 'busy', x: 500, y: 300 },
            { id: 3, name: 'Alex Kim', initials: 'AK', status: 'online', x: 800, y: 200 }
        ];
        
        users.forEach(user => {
            const avatar = document.createElement('div');
            avatar.className = 'user-avatar-spatial';
            avatar.style.left = `${user.x}px`;
            avatar.style.top = `${user.y}px`;
            avatar.innerHTML = `
                ${user.initials}
                <div class="user-status-indicator status-${user.status}"></div>
            `;
            avatar.setAttribute('title', user.name);
            
            if (this.workspace) {
                this.workspace.appendChild(avatar);
            }
        });
    }
    
    /* ===================================
       GESTURE TRACKING
       =================================== */
    setupGestureTracking() {
        let gestureTrail = [];
        
        document.addEventListener('mousemove', (e) => {
            if (e.buttons === 1) { // Left button pressed
                this.createGestureTrail(e.clientX, e.clientY);
            }
        });
    }
    
    createGestureTrail(x, y) {
        const trail = document.createElement('div');
        trail.className = 'gesture-trail';
        trail.style.left = `${x}px`;
        trail.style.top = `${y}px`;
        
        document.body.appendChild(trail);
        
        // Remove after animation
        setTimeout(() => {
            trail.remove();
        }, 1000);
    }
    
    /* ===================================
       KEYBOARD SHORTCUTS
       =================================== */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Workspace rotation with arrow keys
            if (e.code === 'ArrowUp' && e.shiftKey) {
                e.preventDefault();
                this.rotationX += 10;
                this.updateWorkspaceTransform();
            }
            
            if (e.code === 'ArrowDown' && e.shiftKey) {
                e.preventDefault();
                this.rotationX -= 10;
                this.updateWorkspaceTransform();
            }
            
            if (e.code === 'ArrowLeft' && e.shiftKey) {
                e.preventDefault();
                this.rotationY -= 10;
                this.updateWorkspaceTransform();
            }
            
            if (e.code === 'ArrowRight' && e.shiftKey) {
                e.preventDefault();
                this.rotationY += 10;
                this.updateWorkspaceTransform();
            }
            
            // Reset view with Home
            if (e.code === 'Home') {
                e.preventDefault();
                this.handleControlAction('reset');
            }
            
            // Zoom with +/-
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.handleControlAction('zoom-in');
            }
            
            if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.handleControlAction('zoom-out');
            }
        });
    }
    
    /* ===================================
       NOTIFICATIONS
       =================================== */
    createSpatialNotification(message, urgency = 'medium', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `spatial-notification notification-${urgency}`;
        
        // Position based on urgency (z-depth)
        const positions = {
            critical: { x: window.innerWidth / 2 - 150, y: 100 },
            high: { x: window.innerWidth / 2 - 150, y: 150 },
            medium: { x: window.innerWidth / 2 - 150, y: 200 },
            low: { x: window.innerWidth / 2 - 150, y: 250 }
        };
        
        const pos = positions[urgency] || positions.medium;
        notification.style.left = `${pos.x}px`;
        notification.style.top = `${pos.y}px`;
        notification.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 4px;">${urgency.toUpperCase()}</div>
            <div>${message}</div>
        `;
        
        notification.addEventListener('click', () => {
            notification.style.transform = 'translateZ(-2000px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 400);
        });
        
        document.body.appendChild(notification);
        
        // Auto-remove
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.click();
                }
            }, duration);
        }
    }
    
    /* ===================================
       ENVIRONMENTAL MARKERS
       =================================== */
    createFacilityMarker(name, icon, x, y) {
        const marker = document.createElement('div');
        marker.className = 'facility-marker';
        marker.style.left = `${x}px`;
        marker.style.top = `${y}px`;
        marker.innerHTML = `
            <div class="marker-icon">${icon}</div>
            <div class="marker-label">${name}</div>
        `;
        
        marker.addEventListener('click', () => {
            this.showFacilityDetails(name);
        });
        
        const envLayer = document.querySelector('.environmental-layer');
        if (envLayer) {
            envLayer.appendChild(marker);
        }
    }
    
    showFacilityDetails(name) {
        console.log(`Showing details for: ${name}`);
        this.createSpatialNotification(`Loading ${name} details...`, 'medium', 3000);
    }
}

// Initialize Spatial Workspace when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.spatialWorkspace = new SpatialWorkspace();
});
