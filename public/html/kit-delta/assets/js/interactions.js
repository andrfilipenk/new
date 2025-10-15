/**
 * Kit Delta - Advanced Interactions
 * Enhanced user interactions and micro-animations
 */

(function() {
    'use strict';

    /**
     * Initialize all interactions
     */
    function init() {
        initHoverEffects();
        initDragAndDrop();
        initTooltips();
        initAccessibility();
        initPerformanceOptimizations();
    }

    /**
     * Enhanced hover effects
     */
    function initHoverEffects() {
        // Panel tilt effect on mouse move
        document.querySelectorAll('.panel').forEach(panel => {
            panel.addEventListener('mousemove', (e) => {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                
                const rect = panel.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 30;
                const rotateY = (centerX - x) / 30;
                
                panel.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
            
            panel.addEventListener('mouseleave', () => {
                panel.style.transform = '';
            });
        });

        // Glow effect on hexagon hover
        document.querySelectorAll('.hex-service').forEach(hex => {
            hex.addEventListener('mouseenter', function() {
                this.style.filter = 'brightness(1.2)';
            });
            
            hex.addEventListener('mouseleave', function() {
                this.style.filter = '';
            });
        });
    }

    /**
     * Drag and drop for panels (basic implementation)
     */
    function initDragAndDrop() {
        let draggedElement = null;

        document.querySelectorAll('.panel-header').forEach(header => {
            header.style.cursor = 'move';
            header.draggable = true;

            header.addEventListener('dragstart', (e) => {
                draggedElement = header.closest('.panel');
                draggedElement.style.opacity = '0.5';
                e.dataTransfer.effectAllowed = 'move';
            });

            header.addEventListener('dragend', () => {
                if (draggedElement) {
                    draggedElement.style.opacity = '';
                    draggedElement = null;
                }
            });
        });

        document.querySelectorAll('.panel').forEach(panel => {
            panel.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            panel.addEventListener('drop', (e) => {
                e.preventDefault();
                if (draggedElement && draggedElement !== panel) {
                    const parent = panel.parentNode;
                    const panels = Array.from(parent.children);
                    const draggedIndex = panels.indexOf(draggedElement);
                    const targetIndex = panels.indexOf(panel);

                    if (draggedIndex < targetIndex) {
                        parent.insertBefore(draggedElement, panel.nextSibling);
                    } else {
                        parent.insertBefore(draggedElement, panel);
                    }
                }
            });
        });
    }

    /**
     * Tooltip system
     */
    function initTooltips() {
        const tooltip = createTooltipElement();

        document.querySelectorAll('[aria-label]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const text = this.getAttribute('aria-label');
                if (!text) return;

                tooltip.textContent = text;
                tooltip.style.display = 'block';
                positionTooltip(e, tooltip);
            });

            element.addEventListener('mousemove', (e) => {
                positionTooltip(e, tooltip);
            });

            element.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
        });
    }

    function createTooltipElement() {
        const tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.style.cssText = `
            position: fixed;
            background: rgba(30, 33, 57, 0.95);
            color: #e8eaed;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-family: var(--font-mono);
            pointer-events: none;
            z-index: 10000;
            display: none;
            border: 1px solid rgba(0, 245, 255, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        `;
        document.body.appendChild(tooltip);
        return tooltip;
    }

    function positionTooltip(e, tooltip) {
        const offset = 10;
        tooltip.style.left = e.clientX + offset + 'px';
        tooltip.style.top = e.clientY + offset + 'px';
    }

    /**
     * Accessibility enhancements
     */
    function initAccessibility() {
        // Focus visible for keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-nav');
        });

        // Add focus styles
        const style = document.createElement('style');
        style.textContent = `
            .keyboard-nav *:focus {
                outline: 2px solid var(--accent-primary) !important;
                outline-offset: 2px !important;
            }
        `;
        document.head.appendChild(style);

        // Skip links
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.textContent = 'Skip to main content';
        skipLink.className = 'skip-link';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--accent-primary);
            color: #000;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 10000;
            border-radius: 0 0 4px 0;
        `;
        skipLink.addEventListener('focus', () => {
            skipLink.style.top = '0';
        });
        skipLink.addEventListener('blur', () => {
            skipLink.style.top = '-40px';
        });
        document.body.insertBefore(skipLink, document.body.firstChild);

        // ARIA live region for announcements
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = `
            position: absolute;
            left: -10000px;
            width: 1px;
            height: 1px;
            overflow: hidden;
        `;
        document.body.appendChild(liveRegion);
        
        window.announceToScreenReader = (message) => {
            liveRegion.textContent = message;
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        };
    }

    /**
     * Performance optimizations
     */
    function initPerformanceOptimizations() {
        // Lazy load below-fold content
        if ('IntersectionObserver' in window) {
            const lazyObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('loaded');
                        lazyObserver.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            document.querySelectorAll('.panel').forEach(panel => {
                lazyObserver.observe(panel);
            });
        }

        // Throttle scroll events
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) return;
            
            scrollTimeout = setTimeout(() => {
                handleScroll();
                scrollTimeout = null;
            }, 100);
        });

        // Debounce resize events
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                handleResize();
            }, 250);
        });
    }

    function handleScroll() {
        // Add scroll-based effects here
        const scrollY = window.scrollY;
        
        if (scrollY > 100) {
            document.querySelector('.command-bar')?.classList.add('scrolled');
        } else {
            document.querySelector('.command-bar')?.classList.remove('scrolled');
        }
    }

    function handleResize() {
        // Handle responsive changes
        console.log('[Interactions] Window resized');
    }

    /**
     * Context menu enhancement
     */
    function initContextMenu() {
        document.querySelectorAll('.task-item, .notification-item').forEach(item => {
            item.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                showContextMenu(e, item);
            });
        });
    }

    function showContextMenu(e, item) {
        // Simple context menu implementation
        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.cssText = `
            position: fixed;
            left: ${e.clientX}px;
            top: ${e.clientY}px;
            background: var(--surface-color);
            border: 1px solid var(--accent-primary);
            border-radius: 4px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            z-index: 10000;
            min-width: 150px;
        `;

        const options = [
            { label: 'Edit', icon: 'pencil' },
            { label: 'Delete', icon: 'trash' },
            { label: 'Mark as read', icon: 'check' }
        ];

        options.forEach(option => {
            const menuItem = document.createElement('div');
            menuItem.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                font-size: 13px;
                color: var(--text-primary);
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            menuItem.innerHTML = `<i class="bi bi-${option.icon}"></i> ${option.label}`;
            
            menuItem.addEventListener('mouseenter', function() {
                this.style.background = 'rgba(0, 245, 255, 0.1)';
            });
            
            menuItem.addEventListener('mouseleave', function() {
                this.style.background = '';
            });
            
            menuItem.addEventListener('click', () => {
                console.log('[Context Menu]', option.label, item);
                document.body.removeChild(menu);
            });
            
            menu.appendChild(menuItem);
        });

        document.body.appendChild(menu);

        // Remove menu on click outside
        setTimeout(() => {
            document.addEventListener('click', function removeMenu() {
                if (menu.parentNode) {
                    document.body.removeChild(menu);
                }
                document.removeEventListener('click', removeMenu);
            });
        }, 100);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
