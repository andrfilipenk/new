# Kit Void - Abstract Geometric Interface

A bold wireframe UI kit with geometric primitives, isometric grids, and matrix aesthetics.

## Features

- **Wireframe Renderer**: Create 3D geometric shapes (cube, sphere, pyramid, torus)
- **Isometric Command Grid**: 3D navigation in isometric perspective
- **Matrix Timeline**: Infinite grid timeline with geometric event markers
- **Geometric Modals**: Animated 3D construction/destruction
- **Grid System**: Infinite grid with scanline effects
- **Full Accessibility**: Enhanced wireframe visibility, reduced motion support

## Quick Start

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="assets/css/kit-void.css">
    <link rel="stylesheet" href="assets/css/wireframe.css">
    <link rel="stylesheet" href="assets/css/geometric.css">
</head>
<body>
    <div class="kit-void-container">
        <!-- Your content -->
    </div>
    
    <script src="assets/js/wireframe-renderer.js"></script>
    <script src="assets/js/grid-system.js"></script>
    <script src="assets/js/kit-void.js"></script>
    <script>
        const kitVoid = new KitVoid();
    </script>
</body>
</html>
```

## Components

### Wireframe Shapes

```javascript
const renderer = new WireframeRenderer();

// Create cube
const { element, id } = renderer.createCube(container, 100);

// Create sphere
renderer.createSphere(container, 50);

// Create wireframe box
renderer.createWireframeBox(container, 300, 200);
```

### Command Grid

```html
<div class="kit-void-command-grid">
    <div class="kit-void-grid-cell">Node 1</div>
    <div class="kit-void-grid-cell is-active">Node 2</div>
    <!-- More cells -->
</div>
```

### Data Containers

```html
<div class="void-data-box">
    <div class="kit-void-vertex" style="position: absolute; top: -4px; left: -4px;"></div>
    <!-- More vertices at corners -->
    <div class="data-box-content">
        <div class="data-label">METRIC</div>
        <div class="data-value">1234</div>
    </div>
</div>
```

## JavaScript API

```javascript
const kitVoid = new KitVoid(options);

// Wireframe Rendering
const cube = kitVoid.wireframeRenderer.createCube(container, 100);
kitVoid.wireframeRenderer.animateRotation(cube.id, 20000);

// Grid System
kitVoid.gridSystem.createTimelineGrid(container, 2000);
kitVoid.gridSystem.addTimelineEvent(grid, 'task', x, y);

// Alerts & Modals
kitVoid.showAlert(message, type); // info, success, warning, critical
kitVoid.showModal(content, title);
```

## PHP Integration

```php
// Controller
public function metricsAction() {
    $metrics = [
        ['label' => 'Throughput', 'value' => '1.2GB/s', 'trend' => '+18.4%'],
        ['label' => 'Nodes', 'value' => '847', 'trend' => '+5.2%']
    ];
    
    $this->view->setVar('metrics', $metrics);
}
```

## Keyboard Shortcuts

- `Escape` - Close modal
- `Arrow Keys` - Navigate grid cells
- `Enter` - Select grid cell

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Customization

```css
:root {
    --void-cyan: #00FFF0;
    --void-magenta: #FF00FF;
    --void-yellow: #FFFF00;
    --grid-size: 50px;
}
```
