# Kit Professional - Modern Professional UI Kit

## Overview

Kit Professional is a comprehensive, modern UI kit designed for building professional business applications with a focus on task management, project tracking, and team collaboration. It combines the elegant visual appeal of glassmorphic design with practical, data-driven interfaces.

## Features

### Design Philosophy
- **Modern Professionalism**: Clean, contemporary aesthetics suitable for corporate environments
- **Practical Functionality**: Data-rich interfaces with intelligent information hierarchy
- **Responsive Adaptability**: Seamless experience across desktop, tablet, and mobile devices
- **Accessibility-First**: WCAG 2.1 AA compliant components with full keyboard navigation
- **Performance-Oriented**: Lightweight components optimized for fast rendering

### Core Components

#### Layout Components
- **Navigation Header**: Global navigation with search, notifications, and user profile
- **Sidebar Navigation**: Collapsible sidebar with active state management
- **Content Container**: Responsive grid system with flexible layouts

#### Data Display Components
- **KPI Metric Cards**: Display key performance indicators with trend visualization
- **Project Progress Cards**: Visual project phase tracking with completion percentages
- **Task Lists**: Filterable task display with priority and status indicators
- **Data Tables**: Sortable, paginated tables with bulk actions

#### Form Components
- **Input Fields**: Text, email, number inputs with validation states
- **Select Dropdowns**: Standard and searchable select components
- **Textareas**: Multi-line input fields
- **Form Validation**: Real-time inline validation with error messages

#### Feedback Components
- **Toast Notifications**: Auto-dismissing success, error, warning, and info messages
- **Modal Dialogs**: Focus-trapped modals with backdrop
- **Alert Banners**: Persistent page-level notifications

#### Business-Specific Components
- **Upcoming Deadlines Widget**: Priority-sorted deadline notifications
- **Team Activity Feed**: Real-time team member action tracking
- **Assigned Projects Overview**: User's active projects with phase visualization

## File Structure

```
kit-professional/
├── assets/
│   ├── css/
│   │   ├── kit-professional.css    # Design system & layout
│   │   └── components.css          # Component styles
│   └── js/
│       └── kit-professional.js     # Interactive functionality
├── dashboard.html                  # Main dashboard page
├── components.html                 # Component showcase
└── README.md                       # This file
```

## Quick Start

### 1. Basic HTML Structure

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your App - Kit Professional</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Kit Professional Styles -->
    <link rel="stylesheet" href="assets/css/kit-professional.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
    <!-- Your content here -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Kit Professional JS -->
    <script src="assets/js/kit-professional.js"></script>
</body>
</html>
```

### 2. Using Components

#### KPI Card
```html
<div class="pro-kpi-card success">
    <div class="pro-kpi-header">
        <span class="pro-kpi-label">Total Revenue</span>
        <button class="pro-kpi-menu"><i class="bi bi-three-dots"></i></button>
    </div>
    <div class="pro-kpi-value">$124,592</div>
    <div class="pro-kpi-footer">
        <span class="pro-trend positive"><i class="bi bi-arrow-up"></i> +12.5%</span>
        <span class="pro-kpi-period">vs last month</span>
    </div>
    <div class="pro-sparkline">
        <svg viewBox="0 0 100 60">
            <polyline points="0,50 20,45 40,30 60,35 80,20 100,15" />
        </svg>
    </div>
</div>
```

#### Task Item
```html
<div class="pro-task-item priority-high">
    <div class="pro-task-checkbox">
        <input type="checkbox" id="task-1">
    </div>
    <div class="pro-task-content">
        <h3 class="pro-task-title">Complete Q4 financial report</h3>
        <div class="pro-task-meta">
            <span><i class="bi bi-folder"></i> Finance</span>
            <span><i class="bi bi-calendar"></i> Due today at 5:00 PM</span>
            <span class="pro-priority-badge high">High Priority</span>
        </div>
    </div>
</div>
```

#### Toast Notification (JavaScript)
```javascript
// Success toast
KitProfessional.toast.success('Task completed successfully!');

// Error toast
KitProfessional.toast.error('An error occurred!');

// Custom toast
KitProfessional.toast.show({
    type: 'warning',
    title: 'Warning',
    message: 'Please review this action',
    duration: 5000
});
```

## Design System

### Color Palette

#### Primary Colors
- Primary-500: `#3b82f6` - Main brand color
- Primary-700: `#1d4ed8` - Darker variant

#### Semantic Colors
- Success: `#10b981`
- Warning: `#f59e0b`
- Danger: `#ef4444`
- Info: `#3b82f6`

#### Neutral Colors
- Neutral-900: `#111827` - Darkest
- Neutral-700: `#374151` - Body text
- Neutral-500: `#6b7280` - Secondary text
- Neutral-300: `#d1d5db` - Borders
- Neutral-100: `#f3f4f6` - Light backgrounds

### Typography

- **Font Family**: System font stack for optimal performance
- **Font Sizes**: Scale from 0.75rem to 3rem
- **Font Weights**: 400 (normal), 500 (medium), 600 (semibold), 700 (bold)

### Spacing

- **xs**: 4px
- **sm**: 8px
- **md**: 16px
- **lg**: 24px
- **xl**: 32px
- **2xl**: 48px
- **3xl**: 64px

### Border Radius

- **sm**: 6px
- **md**: 8px
- **lg**: 12px
- **xl**: 16px
- **full**: 9999px

## JavaScript API

### Toast Notifications

```javascript
// Show toast with all options
KitProfessional.toast.show({
    type: 'success',      // success, error, warning, info
    title: 'Success',
    message: 'Your message here',
    duration: 5000        // milliseconds, 0 = no auto-dismiss
});

// Shorthand methods
KitProfessional.toast.success(message, title);
KitProfessional.toast.error(message, title);
KitProfessional.toast.warning(message, title);
KitProfessional.toast.info(message, title);
```

### Modal Dialogs

```javascript
// Open modal
KitProfessional.modal.open('modal-id');

// Close modal
KitProfessional.modal.close('modal-id');
```

## Accessibility Features

### Keyboard Navigation
- All interactive elements accessible via Tab key
- Logical tab order following visual flow
- Ctrl+K opens global search
- Escape closes modals and dropdowns
- Focus indicators with high contrast

### Screen Reader Support
- Semantic HTML5 elements (nav, main, aside, article, section)
- ARIA labels for icon-only buttons
- ARIA live regions for dynamic content
- Skip navigation links
- Descriptive alt text for images

### Color Contrast
- Text meets WCAG AA standards (4.5:1 for normal text, 3:1 for large text)
- Status indicators use icons in addition to color
- Color-blind friendly palette

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Android)

## Integration with PHP MVC

### Data Binding Example

```php
// In your controller
$tasks = $this->taskModel->findByUser($userId);
$this->view->render('tasks/index', ['tasks' => $tasks]);
```

```php
<!-- In your view -->
<div class="pro-task-list">
    <?php foreach ($tasks as $task): ?>
    <div class="pro-task-item priority-<?= strtolower($task->priority->name) ?>">
        <div class="pro-task-checkbox">
            <input type="checkbox" id="task-<?= $task->id ?>">
        </div>
        <div class="pro-task-content">
            <h3 class="pro-task-title"><?= htmlspecialchars($task->title) ?></h3>
            <div class="pro-task-meta">
                <span><i class="bi bi-folder"></i> <?= htmlspecialchars($task->category) ?></span>
                <span><i class="bi bi-calendar"></i> <?= $task->due_date ?></span>
                <span class="pro-priority-badge <?= strtolower($task->priority->name) ?>">
                    <?= $task->priority->name ?>
                </span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
```

## Customization

### CSS Custom Properties

All design tokens are defined as CSS custom properties and can be overridden:

```css
:root {
    --primary-500: #your-brand-color;
    --font-family-base: 'Your Font', sans-serif;
    --radius-lg: 16px;
}
```

### Component Variants

Most components support variant classes:
- **KPI Cards**: `.success`, `.info`, `.warning`, `.danger`
- **Buttons**: `.pro-btn-primary`, `.pro-btn-secondary`, `.pro-btn-outline`, `.pro-btn-glass`
- **Sizes**: `.pro-btn-sm`, `.pro-btn-lg`

## Performance Optimization

- Uses Bootstrap 5 CDN for optimal caching
- Minimal custom CSS (< 50KB)
- GPU-accelerated animations (transform, opacity)
- Lazy loading for below-fold widgets
- Debounced search inputs

## License

This UI kit is part of the ProWorkspace project.

## Support

For issues or questions, please refer to the component showcase page (`components.html`) for live examples of all available components.
