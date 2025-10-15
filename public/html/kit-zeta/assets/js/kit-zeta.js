/**
 * Kit Zeta - Holographic Command JavaScript
 * 3D interactions, voice commands, and mission control features
 */

(function() {
    'use strict';

    const CONFIG = {
        voice: {
            enabled: 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window,
            language: 'en-US'
        },
        cube: {
            rotationSpeed: 0.5
        }
    };

    const state = {
        voiceRecognition: null,
        cubeRotation: { x: -15, y: 20 },
        currentModule: 'mission'
    };

    function init() {
        console.log('[Kit Zeta] Initializing Holographic Command...');
        
        initTime();
        initNavigation();
        initVoiceCommands();
        init3DCube();
        initHexGrid();
        initTaskCards();
        initAlerts();
        initKeyboardShortcuts();
        initAccessibility();
        
        console.log('[Kit Zeta] Initialization complete');
    }

    /**
     * System Time
     */
    function initTime() {
        updateTime();
        setInterval(updateTime, 1000);
    }

    function updateTime() {
        const now = new Date();
        const timeStr = now.toTimeString().split(' ')[0];
        const timeEl = document.getElementById('current-time');
        if (timeEl) {
            timeEl.textContent = timeStr;
        }
    }

    /**
     * Navigation
     */
    function initNavigation() {
        document.querySelectorAll('.radial-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.radial-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const module = btn.dataset.module;
                state.currentModule = module;
                console.log('[Navigation] Switched to:', module);
                
                announceToScreenReader(`Switched to ${module} module`);
            });
        });
    }

    /**
     * Voice Commands
     */
    function initVoiceCommands() {
        const voiceBtn = document.getElementById('voiceCommand');
        const voiceInterface = document.getElementById('voiceInterface');
        const voiceCancel = voiceInterface?.querySelector('.voice-cancel');
        
        if (!voiceBtn || !CONFIG.voice.enabled) {
            if (voiceBtn) voiceBtn.style.display = 'none';
            return;
        }
        
        voiceBtn.addEventListener('click', startVoiceCommand);
        
        if (voiceCancel) {
            voiceCancel.addEventListener('click', stopVoiceCommand);
        }
        
        // Initialize speech recognition
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        state.voiceRecognition = new SpeechRecognition();
        state.voiceRecognition.lang = CONFIG.voice.language;
        state.voiceRecognition.continuous = false;
        state.voiceRecognition.interimResults = false;
        
        state.voiceRecognition.onresult = (event) => {
            const command = event.results[0][0].transcript.toLowerCase();
            console.log('[Voice] Command:', command);
            handleVoiceCommand(command);
        };
        
        state.voiceRecognition.onerror = (event) => {
            console.error('[Voice] Error:', event.error);
            stopVoiceCommand();
        };
    }

    function startVoiceCommand() {
        const voiceInterface = document.getElementById('voiceInterface');
        if (!voiceInterface || !state.voiceRecognition) return;
        
        voiceInterface.style.display = 'flex';
        voiceInterface.classList.add('active');
        state.voiceRecognition.start();
        
        announceToScreenReader('Voice command started. Listening...');
    }

    function stopVoiceCommand() {
        const voiceInterface = document.getElementById('voiceInterface');
        if (!voiceInterface || !state.voiceRecognition) return;
        
        voiceInterface.style.display = 'none';
        voiceInterface.classList.remove('active');
        state.voiceRecognition.stop();
        
        announceToScreenReader('Voice command stopped');
    }

    function handleVoiceCommand(command) {
        const voiceRecognized = document.querySelector('.voice-recognized');
        if (voiceRecognized) {
            voiceRecognized.textContent = `Command: "${command}"`;
        }
        
        // Process command
        if (command.includes('mission') || command.includes('status')) {
            switchModule('mission');
        } else if (command.includes('tactical')) {
            switchModule('tactical');
        } else if (command.includes('data') || command.includes('analytics')) {
            switchModule('data');
        } else if (command.includes('communication') || command.includes('message')) {
            switchModule('comms');
        } else if (command.includes('alert')) {
            focusAlerts();
        }
        
        setTimeout(stopVoiceCommand, 2000);
    }

    function switchModule(module) {
        const btn = document.querySelector(`.radial-btn[data-module="${module}"]`);
        if (btn) btn.click();
    }

    function focusAlerts() {
        document.querySelector('.alert-matrix')?.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * 3D Message Cube
     */
    function init3DCube() {
        const cube = document.querySelector('.cube-3d');
        const rotateBtn = document.querySelector('.cube-rotate-btn');
        
        if (!cube) return;
        
        // Mouse drag rotation
        let isDragging = false;
        let startX, startY;
        
        cube.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            state.cubeRotation.y += deltaX * CONFIG.cube.rotationSpeed;
            state.cubeRotation.x -= deltaY * CONFIG.cube.rotationSpeed;
            
            updateCubeRotation();
            
            startX = e.clientX;
            startY = e.clientY;
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
        });
        
        // Rotate button
        if (rotateBtn) {
            rotateBtn.addEventListener('click', () => {
                state.cubeRotation.y += 90;
                updateCubeRotation();
            });
        }
        
        // Click on cube face
        cube.querySelectorAll('.cube-face').forEach(face => {
            face.addEventListener('click', () => {
                const channel = face.dataset.channel;
                console.log('[Cube] Selected channel:', channel);
                announceToScreenReader(`Selected ${channel} channel`);
            });
        });
    }

    function updateCubeRotation() {
        const cube = document.querySelector('.cube-3d');
        if (!cube) return;
        
        cube.style.transform = `rotateX(${state.cubeRotation.x}deg) rotateY(${state.cubeRotation.y}deg)`;
    }

    /**
     * Hexagonal Grid
     */
    function initHexGrid() {
        document.querySelectorAll('.hex-tile').forEach(tile => {
            tile.addEventListener('click', () => {
                const status = tile.dataset.status;
                const label = tile.querySelector('.hex-label')?.textContent;
                console.log('[Hex] Clicked:', label, status);
                
                // Show details (placeholder)
                announceToScreenReader(`${label} system - ${status}`);
            });
            
            // Keyboard support
            tile.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    tile.click();
                }
            });
        });
    }

    /**
     * Task Cards with Gesture Support
     */
    function initTaskCards() {
        document.querySelectorAll('.task-card').forEach(card => {
            let startX = 0;
            let startY = 0;
            let currentX = 0;
            let currentY = 0;
            
            card.addEventListener('mousedown', (e) => {
                if (!card.dataset.gestureEnabled) return;
                startX = e.clientX;
                startY = e.clientY;
            });
            
            card.addEventListener('mousemove', (e) => {
                if (!startX) return;
                
                currentX = e.clientX - startX;
                currentY = e.clientY - startY;
                
                if (Math.abs(currentX) > 50) {
                    card.classList.add(currentX > 0 ? 'swiping-right' : 'swiping-left');
                } else if (currentY < -30) {
                    card.classList.add('swiping-up');
                }
            });
            
            card.addEventListener('mouseup', () => {
                if (Math.abs(currentX) > 100) {
                    handleSwipe(card, currentX > 0 ? 'right' : 'left');
                } else if (currentY < -50) {
                    handleSwipe(card, 'up');
                }
                
                card.classList.remove('swiping-right', 'swiping-left', 'swiping-up');
                startX = 0;
                currentX = 0;
                currentY = 0;
            });
            
            // Button actions
            card.querySelectorAll('.swipe-action').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const action = btn.classList.contains('complete') ? 'right' :
                                 btn.classList.contains('defer') ? 'left' : 'up';
                    handleSwipe(card, action);
                });
            });
        });
    }

    function handleSwipe(card, direction) {
        const title = card.querySelector('.task-title')?.textContent;
        
        switch(direction) {
            case 'right':
                console.log('[Task] Completed:', title);
                card.style.opacity = '0.5';
                announceToScreenReader('Task marked as complete');
                setTimeout(() => card.remove(), 300);
                break;
            case 'left':
                console.log('[Task] Deferred:', title);
                announceToScreenReader('Task deferred');
                break;
            case 'up':
                console.log('[Task] Escalated:', title);
                card.dataset.priority = 'critical';
                announceToScreenReader('Task priority escalated to critical');
                break;
        }
    }

    /**
     * Alerts
     */
    function initAlerts() {
        document.querySelectorAll('.alert-item').forEach(alert => {
            const respondBtn = alert.querySelector('.alert-btn.respond');
            const dismissBtn = alert.querySelector('.alert-btn.dismiss');
            const acknowledgeBtn = alert.querySelector('.alert-btn.acknowledge');
            
            if (respondBtn) {
                respondBtn.addEventListener('click', () => {
                    const title = alert.querySelector('.alert-title')?.textContent;
                    console.log('[Alert] Responding to:', title);
                    announceToScreenReader(`Responding to alert: ${title}`);
                });
            }
            
            if (dismissBtn) {
                dismissBtn.addEventListener('click', () => {
                    alert.style.animation = 'fadeOut 0.3s';
                    setTimeout(() => alert.remove(), 300);
                    announceToScreenReader('Alert dismissed');
                });
            }
            
            if (acknowledgeBtn) {
                acknowledgeBtn.addEventListener('click', () => {
                    alert.style.opacity = '0.5';
                    acknowledgeBtn.textContent = 'ACKNOWLEDGED';
                    acknowledgeBtn.disabled = true;
                    announceToScreenReader('Alert acknowledged');
                });
            }
        });
    }

    /**
     * Keyboard Shortcuts
     */
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // V for voice command
            if (e.key === 'v' || e.key === 'V') {
                if (!e.ctrlKey && !e.metaKey) {
                    startVoiceCommand();
                }
            }
            
            // Esc to close
            if (e.key === 'Escape') {
                stopVoiceCommand();
            }
            
            // Number keys for modules
            if (e.key >= '1' && e.key <= '4') {
                const modules = ['mission', 'tactical', 'data', 'comms'];
                switchModule(modules[parseInt(e.key) - 1]);
            }
        });
    }

    /**
     * Accessibility
     */
    function initAccessibility() {
        // Live region
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.style.cssText = 'position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden';
        document.body.appendChild(liveRegion);
        
        window.announceToScreenReader = (message) => {
            liveRegion.textContent = message;
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        };
        
        // Focus management
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-nav');
        });
        
        // Focus styles
        const style = document.createElement('style');
        style.textContent = `
            .keyboard-nav *:focus {
                outline: 3px solid var(--accent-blue) !important;
                outline-offset: 2px !important;
            }
        `;
        document.head.appendChild(style);
    }

    /**
     * Countdown Timers
     */
    function updateCountdowns() {
        document.querySelectorAll('.countdown').forEach(countdown => {
            const [hours, minutes, seconds] = countdown.textContent.split(':').map(Number);
            let totalSeconds = hours * 3600 + minutes * 60 + seconds;
            
            if (totalSeconds > 0) {
                totalSeconds--;
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                countdown.textContent = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            }
        });
    }

    setInterval(updateCountdowns, 1000);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export API
    window.KitZeta = {
        startVoiceCommand,
        stopVoiceCommand,
        switchModule,
        state
    };

})();
