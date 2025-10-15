# Kit Prism - Liquid Crystal Interface

A futuristic UI kit with iridescent effects, liquid morphing, and holographic displays.

## Features

- **Liquid Crystal Navigation**: Morphing navigation with ripple effects
- **Holographic Data Panels**: Frosted glass panels with refraction
- **Prismatic Data Flow**: Flowing rainbow light beams
- **Iridescence Engine**: Dynamic rainbow shimmer effects
- **Spectrum Command Center**: Circular command wheel interface
- **Full Accessibility**: High contrast mode, reduced transparency support

## Quick Start

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="assets/css/kit-prism.css">
    <link rel="stylesheet" href="assets/css/iridescence.css">
    <link rel="stylesheet" href="assets/css/liquid-morph.css">
</head>
<body>
    <div class="kit-prism-container">
        <!-- Your content -->
    </div>
    
    <script src="assets/js/iridescence-engine.js"></script>
    <script src="assets/js/morph-animations.js"></script>
    <script src="assets/js/kit-prism.js"></script>
    <script>
        const kitPrism = new KitPrism();
    </script>
</body>
</html>
```

## Components

### Holographic Panels

```html
<div class="kit-prism-panel kit-prism-glass">
    <h3>Title</h3>
    <p>Content</p>
</div>
```

### Liquid Navigation

```html
<nav class="kit-prism-nav">
    <button class="kit-prism-nav-item">Item 1</button>
    <button class="kit-prism-nav-item is-active">Item 2</button>
</nav>
```

## JavaScript API

```javascript
const kitPrism = new KitPrism(options);

// Iridescence
kitPrism.iridescence.register(element, {
    type: 'shimmer', // shimmer, holographic, rainbow, chromatic
    intensity: 1,
    speed: 1
});

// Morphing
kitPrism.morphAnimations.morphShape(element, from, to, duration);
kitPrism.morphAnimations.createRipple(element, x, y);
kitPrism.morphAnimations.applyLiquidFlow(element, direction);

// Notifications
kitPrism.showNotification(message, type);
```

## PHP Integration

```php
// Controller
public function dashboardAction() {
    $panels = [
        ['label' => 'Revenue', 'value' => '$3.2M', 'trend' => '+12.5%'],
        ['label' => 'Users', 'value' => '15.4K', 'trend' => '+6.8%']
    ];
    
    $this->view->setVar('panels', $panels);
}
```

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Customization

```css
:root {
    --prism-blue: #4ECDC4;
    --prism-pink: #FF6B9D;
    --prism-gold: #FFD166;
}
```
