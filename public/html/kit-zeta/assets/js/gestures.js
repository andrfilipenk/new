/**
 * Kit Zeta - Gesture Controls
 * Touch and mouse gesture support for holographic interfaces
 */

(function() {
    'use strict';

    const GESTURE_THRESHOLD = {
        swipe: 50,
        pinch: 20,
        rotate: 15
    };

    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;
    let initialDistance = 0;
    let currentScale = 1;

    function init() {
        console.log('[Gestures] Initializing touch and mouse gestures...');
        
        initTouchGestures();
        initMouseGestures();
        initPinchZoom();
    }

    /**
     * Touch Gestures
     */
    function initTouchGestures() {
        document.addEventListener('touchstart', handleTouchStart, { passive: false });
        document.addEventListener('touchmove', handleTouchMove, { passive: false });
        document.addEventListener('touchend', handleTouchEnd, { passive: false });
    }

    function handleTouchStart(e) {
        if (e.touches.length === 1) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        } else if (e.touches.length === 2) {
            const dx = e.touches[0].clientX - e.touches[1].clientX;
            const dy = e.touches[0].clientY - e.touches[1].clientY;
            initialDistance = Math.sqrt(dx * dx + dy * dy);
        }
    }

    function handleTouchMove(e) {
        if (e.touches.length === 1) {
            const element = e.target.closest('.task-card, .message-cube, .holo-panel');
            if (!element) return;
            
            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            const deltaX = currentX - touchStartX;
            const deltaY = currentY - touchStartY;
            
            // Visual feedback
            if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
                e.preventDefault();
                
                if (element.classList.contains('task-card')) {
                    handleTaskSwipe(element, deltaX, deltaY);
                } else if (element.classList.contains('message-cube')) {
                    handleCubeRotate(deltaX, deltaY);
                }
            }
        }
    }

    function handleTouchEnd(e) {
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        
        const element = e.target.closest('.task-card');
        if (element) {
            finalizeSwipe(element, deltaX, deltaY);
        }
        
        resetTouchValues();
    }

    function resetTouchValues() {
        touchStartX = 0;
        touchStartY = 0;
        touchEndX = 0;
        touchEndY = 0;
    }

    /**
     * Mouse Gestures (Drag)
     */
    function initMouseGestures() {
        let isDragging = false;
        let dragElement = null;
        let startX, startY;
        
        document.addEventListener('mousedown', (e) => {
            const element = e.target.closest('[data-gesture-enabled]');
            if (!element) return;
            
            isDragging = true;
            dragElement = element;
            startX = e.clientX;
            startY = e.clientY;
            
            element.style.cursor = 'grabbing';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging || !dragElement) return;
            
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            if (dragElement.classList.contains('task-card')) {
                handleTaskSwipe(dragElement, deltaX, deltaY);
            }
        });
        
        document.addEventListener('mouseup', (e) => {
            if (!isDragging || !dragElement) return;
            
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            if (dragElement.classList.contains('task-card')) {
                finalizeSwipe(dragElement, deltaX, deltaY);
            }
            
            dragElement.style.cursor = '';
            isDragging = false;
            dragElement = null;
        });
    }

    /**
     * Task Card Swipe Handlers
     */
    function handleTaskSwipe(element, deltaX, deltaY) {
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            // Horizontal swipe
            if (deltaX > GESTURE_THRESHOLD.swipe) {
                element.classList.add('swiping-right');
                element.classList.remove('swiping-left', 'swiping-up');
            } else if (deltaX < -GESTURE_THRESHOLD.swipe) {
                element.classList.add('swiping-left');
                element.classList.remove('swiping-right', 'swiping-up');
            }
        } else {
            // Vertical swipe
            if (deltaY < -GESTURE_THRESHOLD.swipe) {
                element.classList.add('swiping-up');
                element.classList.remove('swiping-right', 'swiping-left');
            }
        }
    }

    function finalizeSwipe(element, deltaX, deltaY) {
        const threshold = GESTURE_THRESHOLD.swipe * 2;
        
        if (deltaX > threshold) {
            // Swipe right - Complete
            completeTask(element);
        } else if (deltaX < -threshold) {
            // Swipe left - Defer
            deferTask(element);
        } else if (deltaY < -threshold) {
            // Swipe up - Escalate
            escalateTask(element);
        }
        
        // Reset classes
        element.classList.remove('swiping-right', 'swiping-left', 'swiping-up');
    }

    function completeTask(element) {
        element.style.transform = 'translateX(300px)';
        element.style.opacity = '0';
        
        setTimeout(() => {
            element.remove();
            announceAction('Task completed');
        }, 300);
    }

    function deferTask(element) {
        element.style.transform = 'translateX(-300px)';
        element.style.opacity = '0.3';
        
        setTimeout(() => {
            element.style.transform = '';
            element.style.opacity = '';
            announceAction('Task deferred');
        }, 300);
    }

    function escalateTask(element) {
        element.dataset.priority = 'critical';
        element.style.borderColor = 'var(--critical)';
        
        const indicator = element.querySelector('.task-indicator');
        if (indicator) {
            indicator.classList.add('critical');
        }
        
        announceAction('Task escalated to critical priority');
    }

    /**
     * Message Cube Rotation
     */
    function handleCubeRotate(deltaX, deltaY) {
        const cube = document.querySelector('.cube-3d');
        if (!cube) return;
        
        const rotationY = deltaX * 0.5;
        const rotationX = -deltaY * 0.5;
        
        const currentTransform = cube.style.transform || 'rotateX(-15deg) rotateY(20deg)';
        const match = currentTransform.match(/rotateX\(([-\d.]+)deg\) rotateY\(([-\d.]+)deg\)/);
        
        if (match) {
            const newX = parseFloat(match[1]) + rotationX;
            const newY = parseFloat(match[2]) + rotationY;
            cube.style.transform = `rotateX(${newX}deg) rotateY(${newY}deg)`;
        }
    }

    /**
     * Pinch to Zoom
     */
    function initPinchZoom() {
        let initialPinchDistance = 0;
        
        document.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                const dx = e.touches[0].clientX - e.touches[1].clientX;
                const dy = e.touches[0].clientY - e.touches[1].clientY;
                initialPinchDistance = Math.sqrt(dx * dx + dy * dy);
            }
        });
        
        document.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2) {
                e.preventDefault();
                
                const dx = e.touches[0].clientX - e.touches[1].clientX;
                const dy = e.touches[0].clientY - e.touches[1].clientY;
                const currentDistance = Math.sqrt(dx * dx + dy * dy);
                
                const scale = currentDistance / initialPinchDistance;
                const element = e.target.closest('.message-cube, .holo-panel');
                
                if (element) {
                    element.style.transform = `scale(${Math.min(Math.max(scale, 0.8), 1.5)})`;
                }
            }
        });
        
        document.addEventListener('touchend', (e) => {
            const element = document.querySelector('[style*="scale"]');
            if (element) {
                setTimeout(() => {
                    element.style.transform = '';
                }, 300);
            }
        });
    }

    /**
     * Flick Gesture
     */
    function detectFlick(deltaX, deltaY, duration) {
        const velocity = Math.sqrt(deltaX * deltaX + deltaY * deltaY) / duration;
        return velocity > 1.5; // threshold for flick
    }

    /**
     * Utility Functions
     */
    function announceAction(message) {
        if (window.announceToScreenReader) {
            window.announceToScreenReader(message);
        }
        console.log('[Gesture]', message);
    }

    /**
     * Haptic Feedback (if available)
     */
    function hapticFeedback(type = 'light') {
        if ('vibrate' in navigator) {
            const patterns = {
                light: 10,
                medium: 20,
                heavy: 30
            };
            navigator.vibrate(patterns[type] || patterns.light);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export API
    window.KitZetaGestures = {
        hapticFeedback,
        GESTURE_THRESHOLD
    };

})();
