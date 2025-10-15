# Kit Nebula - Cosmic Intelligence Interface

A space-themed futuristic UI kit with cosmic phenomena, particle systems, and 3D effects.

## Features

- **Stellar Navigation Orb**: 3D rotating navigation sphere with orbital nodes
- **Nebula Data Cards**: Floating cosmic cloud cards for metrics display
- **Particle Engine**: High-performance particle system with 5 particle types
- **3D Transform Effects**: Mouse-controlled rotations and parallax effects
- **Aurora Command Palette**: Quick command access with visual feedback
- **Stellar Notifications**: Shooting star notification system
- **Full Accessibility**: ARIA labels, keyboard navigation, reduced motion support

## Quick Start

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/css/kit-nebula.css">
    <link rel="stylesheet" href="assets/css/particles.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body>
    <div class="kit-nebula-starfield"></div>
    <div class="kit-nebula-container">
        <!-- Your content here -->
    </div>
    
    <script src="assets/js/particle-engine.js"></script>
    <script src="assets/js/three-d-effects.js"></script>
    <script src="assets/js/kit-nebula.js"></script>
    <script>
        const kitNebula = new KitNebula({
            enableParticles: true,
            enable3D: true,
            particleCount: 200
        });
    </script>
</body>
</html>
```

## Components

### Stellar Navigation Orb

3D rotating sphere with navigation nodes:

```html
<div class="kit-nebula-stellar-orb kit-nebula-perspective">
    <div class="kit-nebula-orb-sphere kit-nebula-preserve-3d">
        <div class="kit-nebula-orb-core"></div>
        <button class="kit-nebula-orb-node">🏠</button>
        <!-- More nodes -->
    </div>
</div>
```

### Nebula Data Cards

Floating cosmic cloud cards:

```html
<div class="kit-nebula-data-cards">
    <div class="kit-nebula-data-card">
        <div class="kit-nebula-card-content">
            <div class="kit-nebula-card-label">Label</div>
            <div class="kit-nebula-card-value">Value</div>
            <div class="kit-nebula-card-trend positive">+5.2%</div>
        </div>
    </div>
</div>
```

## JavaScript API

### KitNebula Class

```javascript
const kitNebula = new KitNebula(options);

// Options
{
    container: document.body,        // Container element
    enableParticles: true,          // Enable particle system
    enable3D: true,                 // Enable 3D effects
    particleCount: 200              // Max particles
}

// Methods
kitNebula.showCommandPalette();     // Show command palette
kitNebula.showNotification(msg, type); // Show notification
kitNebula.emitParticles(type, x, y, count); // Emit particles
kitNebula.destroy();                // Cleanup
```

### Particle Engine

```javascript
const particleEngine = new ParticleEngine(options);

// Particle Types
particleEngine.emit('stardust', x, y, count);  // Twinkling stars
particleEngine.emit('nebula', x, y, count);    // Cosmic gas
particleEngine.emit('energy', x, y, count);    // Energy streams
particleEngine.emit('meteor', x, y, count);    // Meteor trails
particleEngine.emit('burst', x, y, count);     // Explosion bursts
```

### 3D Effects

```javascript
const threeDEffects = new ThreeDEffects();

// Apply effects
threeDEffects.applyMouseRotation(element, intensity);
threeDEffects.applyParallax(element, depth);
threeDEffects.applyGravitationalLens(element, event);
threeDEffects.rotateOnAxis(element, 'y', 180);
```

## PHP Backend Integration

### Controller Example

```php
<?php
namespace App\Main\Controller;

class DashboardController extends \Core\Mvc\Controller\AbstractController {
    public function indexAction() {
        $metrics = [
            ['label' => 'Revenue', 'value' => '$2.4M', 'trend' => '+15.3%'],
            ['label' => 'Users', 'value' => '12.8K', 'trend' => '+8.2%'],
            ['label' => 'Conversion', 'value' => '3.7%', 'trend' => '+2.1%'],
        ];
        
        $this->view->setVar('metrics', $metrics);
        $this->view->setLayout('kit-nebula/dashboard');
    }
    
    public function metricsApiAction() {
        $this->response->setJsonContent([
            'success' => true,
            'data' => [
                'metrics' => $this->getMetrics(),
                'timestamp' => date('c')
            ]
        ]);
        return $this->response;
    }
}
```

### View Template (.phtml)

```php
<!-- app/views/dashboard/index.phtml -->
<div class="kit-nebula-data-cards">
    <?php foreach ($metrics as $metric): ?>
    <div class="kit-nebula-data-card">
        <div class="kit-nebula-card-content">
            <div class="kit-nebula-card-label"><?= $metric['label'] ?></div>
            <div class="kit-nebula-card-value"><?= $metric['value'] ?></div>
            <div class="kit-nebula-card-trend positive"><?= $metric['trend'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
const kitNebula = new KitNebula();

// Fetch real-time updates
setInterval(async () => {
    const response = await fetch('/dashboard/metrics-api');
    const data = await response.json();
    
    data.data.metrics.forEach((metric, index) => {
        kitNebula.components.dataCards.updateData(index, metric);
    });
}, 5000);
</script>
```

## Keyboard Shortcuts

- `Ctrl+K` / `Cmd+K` - Open command palette
- `Escape` - Close command palette
- `Tab` / `Shift+Tab` - Navigate focusable elements
- `Arrow Keys` - Navigate grid/orb elements

## Accessibility

### Reduced Motion

The kit automatically detects `prefers-reduced-motion: reduce` and:
- Disables particle systems
- Replaces 3D transforms with 2D
- Reduces animation duration by 50%
- Removes pulsing/rotating animations

### Screen Readers

All components include:
- Semantic HTML structure
- ARIA labels and roles
- Live regions for dynamic content
- Alternative text for visual effects

### Keyboard Navigation

All interactive elements are keyboard accessible with visible focus indicators.

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Performance

- 60 FPS animations
- GPU acceleration
- Max 500 particles
- Object pooling for particles
- <200KB total JavaScript

## Customization

### Colors

Override CSS custom properties:

```css
:root {
    --nebula-deep-space: #000814;
    --nebula-cyan: #00F5FF;
    --nebula-purple: #7209B7;
    --nebula-gold: #FFBA08;
    /* etc. */
}
```

### Fonts

Default fonts: Orbitron, Rajdhani, Inter, JetBrains Mono

Change via CSS custom properties:

```css
:root {
    --font-display: 'YourFont', sans-serif;
}
```

## License

This UI kit is provided as-is for enterprise dashboard use.
