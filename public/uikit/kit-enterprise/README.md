# Enterprise UI Kit

A professional, functional-first design system for business applications. Built for speed, reliability, and cognitive clarity with WCAG 2.1 Level AA accessibility compliance.

## Overview

The Enterprise UI Kit is a lightweight, high-performance UI framework designed for serious business applications where functionality and clarity take precedence over decorative elements. It features:

- **Functional-First Design** - Every visual element serves a clear purpose
- **Sharp Geometry** - Minimal border radius (0-2px), rectangular layouts, crisp edges
- **Color Restraint** - Limited palette (3-4 colors max), high contrast, semantic usage
- **Performance Optimized** - Lightweight CSS (~40KB gzipped), minimal JavaScript
- **Accessibility Compliant** - WCAG 2.1 Level AA standards
- **Keyboard-Friendly** - Complete keyboard navigation support

## Quick Start

### 1. Include Required Files

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Bootstrap CSS (Foundation) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Enterprise UI Kit CSS -->
    <link href="assets/css/kit-enterprise.css" rel="stylesheet">
</head>
<body>
    <!-- Your content here -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enterprise UI Kit JS -->
    <script src="assets/js/kit-enterprise.js"></script>
</body>
</html>
```

### 2. Basic Layout Structure

```html
<div class="enterprise-layout">
    <header class="enterprise-header">
        <!-- Header content -->
    </header>
    
    <div class="enterprise-main">
        <aside class="enterprise-sidebar">
            <!-- Navigation -->
        </aside>
        
        <main class="enterprise-content">
            <div class="enterprise-toolbar">
                <!-- Toolbar actions -->
            </div>
            
            <div class="enterprise-content__body">
                <!-- Main content -->
            </div>
        </main>
    </div>
    
    <footer class="enterprise-footer">
        <!-- Footer content -->
    </footer>
</div>
```

### 3. View Examples

- **Dashboard Template**: Open `dashboard.html` for a complete working example
- **Component Showcase**: Open `components.html` to see all available components

## Core Components

### Buttons

```html
<!-- Primary Action -->
<button class="enterprise-button enterprise-button--primary">Primary</button>

<!-- Secondary Action -->
<button class="enterprise-button enterprise-button--secondary">Secondary</button>

<!-- Destructive Action -->
<button class="enterprise-button enterprise-button--danger">Delete</button>

<!-- Tertiary Action -->
<button class="enterprise-button enterprise-button--ghost">Cancel</button>

<!-- With Icon -->
<button class="enterprise-button enterprise-button--primary">
    <i class="bi bi-plus-lg"></i>
    New Item
</button>

<!-- Icon Only -->
<button class="enterprise-button enterprise-button--ghost enterprise-button--icon-only">
    <i class="bi bi-gear"></i>
</button>

<!-- Small Size -->
<button class="enterprise-button enterprise-button--primary enterprise-button--small">Small</button>
```

**States**: Default, Hover, Active, Focus, Disabled

### Form Inputs

```html
<!-- Text Input -->
<div class="enterprise-form-group">
    <label class="enterprise-label">Label</label>
    <input type="text" class="enterprise-input" placeholder="Enter text">
    <span class="enterprise-help-text">Helper text</span>
</div>

<!-- Required Field -->
<div class="enterprise-form-group">
    <label class="enterprise-label enterprise-label--required">Required</label>
    <input type="text" class="enterprise-input" required>
</div>

<!-- Validation States -->
<input class="enterprise-input enterprise-input--success" value="Valid">
<span class="enterprise-success-text">Input is valid</span>

<input class="enterprise-input enterprise-input--error" value="Invalid">
<span class="enterprise-error-text">This field has an error</span>

<!-- Select Dropdown -->
<select class="enterprise-select">
    <option>Option 1</option>
    <option>Option 2</option>
</select>

<!-- Textarea -->
<textarea class="enterprise-textarea" placeholder="Enter text"></textarea>

<!-- Checkbox -->
<label class="enterprise-checkbox">
    <input type="checkbox">
    Checkbox label
</label>

<!-- Radio Button -->
<label class="enterprise-radio">
    <input type="radio" name="group">
    Radio label
</label>
```

### Cards

```html
<!-- Basic Card -->
<div class="enterprise-card">
    <div class="enterprise-card__header">
        <h3 class="enterprise-card__title">Card Title</h3>
        <div class="enterprise-card__actions">
            <button class="enterprise-button enterprise-button--ghost enterprise-button--small">Action</button>
        </div>
    </div>
    <div class="enterprise-card__body">
        Card content goes here
    </div>
    <div class="enterprise-card__footer">
        Card footer (optional)
    </div>
</div>

<!-- Metric Card -->
<div class="enterprise-metric-card">
    <div class="enterprise-metric-card__label">Total Users</div>
    <div class="enterprise-metric-card__value">1,247</div>
    <div class="enterprise-metric-card__trend enterprise-metric-card__trend--up">
        <i class="bi bi-arrow-up"></i>
        <span>12% increase</span>
    </div>
</div>
```

### Data Grid

```html
<div class="enterprise-grid">
    <table class="enterprise-grid__table">
        <thead class="enterprise-grid__header">
            <tr>
                <th class="enterprise-grid__header-cell enterprise-grid__header-cell--checkbox">
                    <input type="checkbox">
                </th>
                <th class="enterprise-grid__header-cell enterprise-grid__header-cell--sortable" data-column="id">
                    ID
                    <span class="enterprise-grid__sort-icon">▲</span>
                </th>
                <th class="enterprise-grid__header-cell enterprise-grid__header-cell--sortable" data-column="name">
                    Name
                    <span class="enterprise-grid__sort-icon">▲</span>
                </th>
            </tr>
        </thead>
        <tbody class="enterprise-grid__body">
            <tr class="enterprise-grid__row" data-row-id="1">
                <td class="enterprise-grid__cell enterprise-grid__cell--checkbox">
                    <input type="checkbox">
                </td>
                <td class="enterprise-grid__cell">#001</td>
                <td class="enterprise-grid__cell">Item Name</td>
            </tr>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <div class="enterprise-pagination">
        <div class="enterprise-pagination__info">Showing 1 to 10 of 100 entries</div>
        <div class="enterprise-pagination__controls">
            <button class="enterprise-pagination__button" disabled>Previous</button>
            <button class="enterprise-pagination__button enterprise-pagination__button--active">1</button>
            <button class="enterprise-pagination__button">2</button>
            <button class="enterprise-pagination__button">Next</button>
        </div>
    </div>
</div>
```

**Features**: 
- Column sorting (click header)
- Row selection (checkbox)
- Keyboard navigation (arrow keys)
- Double-click to activate
- Context menu (right-click)

### Navigation

```html
<!-- Sidebar Navigation -->
<nav class="enterprise-nav">
    <div class="enterprise-nav__group">
        <div class="enterprise-nav__group-header">Group Name</div>
        <a href="#" class="enterprise-nav-item enterprise-nav-item--active">
            <i class="bi bi-speedometer2 enterprise-nav-item__icon"></i>
            <span class="enterprise-nav-item__text">Dashboard</span>
        </a>
        <a href="#" class="enterprise-nav-item">
            <i class="bi bi-list-check enterprise-nav-item__icon"></i>
            <span class="enterprise-nav-item__text">Tasks</span>
        </a>
    </div>
</nav>

<!-- Tabs -->
<div class="enterprise-tabs">
    <button class="enterprise-tab enterprise-tab--active" data-tab="tab1">Tab 1</button>
    <button class="enterprise-tab" data-tab="tab2">Tab 2</button>
</div>

<div class="enterprise-tab-content enterprise-tab-content--active" id="tab1">
    Content for Tab 1
</div>
<div class="enterprise-tab-content" id="tab2">
    Content for Tab 2
</div>

<!-- Breadcrumb -->
<nav class="enterprise-breadcrumb">
    <a href="#" class="enterprise-breadcrumb__item">Home</a>
    <span class="enterprise-breadcrumb__separator">/</span>
    <a href="#" class="enterprise-breadcrumb__item">Products</a>
    <span class="enterprise-breadcrumb__separator">/</span>
    <span class="enterprise-breadcrumb__item enterprise-breadcrumb__item--active">Details</span>
</nav>
```

### Status Badges

```html
<span class="enterprise-badge enterprise-badge--success">Completed</span>
<span class="enterprise-badge enterprise-badge--warning">Pending</span>
<span class="enterprise-badge enterprise-badge--danger">Failed</span>
<span class="enterprise-badge enterprise-badge--info">Info</span>
<span class="enterprise-badge enterprise-badge--default">Default</span>
```

### Modal Dialogs

```html
<div class="enterprise-modal" id="myModal">
    <div class="enterprise-modal__backdrop"></div>
    <div class="enterprise-modal__container">
        <div class="enterprise-modal__header">
            <h2 class="enterprise-modal__title">Modal Title</h2>
            <button class="enterprise-modal__close">×</button>
        </div>
        <div class="enterprise-modal__body">
            Modal content goes here
        </div>
        <div class="enterprise-modal__footer">
            <button class="enterprise-button enterprise-button--secondary">Cancel</button>
            <button class="enterprise-button enterprise-button--primary">Confirm</button>
        </div>
    </div>
</div>

<script>
// Open modal
const modal = new EnterpriseUI.Modal(document.getElementById('myModal'));
modal.open();

// Close modal
modal.close();
</script>
```

### Alerts

```html
<!-- Success Alert -->
<div class="enterprise-alert enterprise-alert--success">
    <div class="enterprise-alert__icon">
        <i class="bi bi-check-circle"></i>
    </div>
    <div class="enterprise-alert__content">
        <div class="enterprise-alert__title">Success</div>
        <div class="enterprise-alert__message">Operation completed successfully</div>
    </div>
</div>

<!-- Warning Alert -->
<div class="enterprise-alert enterprise-alert--warning">
    <div class="enterprise-alert__icon">
        <i class="bi bi-exclamation-triangle"></i>
    </div>
    <div class="enterprise-alert__content">
        <div class="enterprise-alert__title">Warning</div>
        <div class="enterprise-alert__message">Please review before proceeding</div>
    </div>
</div>

<!-- Danger Alert -->
<div class="enterprise-alert enterprise-alert--danger">
    <div class="enterprise-alert__icon">
        <i class="bi bi-x-circle"></i>
    </div>
    <div class="enterprise-alert__content">
        <div class="enterprise-alert__title">Error</div>
        <div class="enterprise-alert__message">An error occurred</div>
    </div>
</div>

<!-- Info Alert -->
<div class="enterprise-alert enterprise-alert--info">
    <div class="enterprise-alert__icon">
        <i class="bi bi-info-circle"></i>
    </div>
    <div class="enterprise-alert__content">
        <div class="enterprise-alert__title">Information</div>
        <div class="enterprise-alert__message">Here's some helpful information</div>
    </div>
</div>
```

### Toast Notifications

```javascript
// Success toast
EnterpriseToast.success('Success!', 'Operation completed successfully');

// Warning toast
EnterpriseToast.warning('Warning', 'Please review this action');

// Error toast
EnterpriseToast.danger('Error', 'An error occurred');

// Info toast
EnterpriseToast.info('Info', 'Here is some information');

// Custom duration (milliseconds)
EnterpriseToast.success('Title', 'Message', 3000);
```

## JavaScript API

### Initialization

The UI Kit auto-initializes all components on page load. Manual initialization:

```javascript
// Initialize all components
EnterpriseUI.init();

// Or initialize specific components
const sidebar = new EnterpriseUI.Sidebar(document.querySelector('.enterprise-sidebar'));
const grid = new EnterpriseUI.DataGrid(document.querySelector('.enterprise-grid'));
const modal = new EnterpriseUI.Modal(document.querySelector('.enterprise-modal'));
const tabs = new EnterpriseUI.Tabs(document.querySelector('.enterprise-tabs'));
```

### Sidebar

```javascript
const sidebar = new EnterpriseUI.Sidebar(element);

// Toggle sidebar
sidebar.toggle();

// Collapse sidebar
sidebar.collapse();

// Expand sidebar
sidebar.expand();

// Listen for events
window.addEventListener('sidebar:toggled', (e) => {
    console.log('Sidebar collapsed:', e.detail.collapsed);
});
```

### Data Grid

```javascript
const grid = new EnterpriseUI.DataGrid(element);

// Get selected rows
const selectedRows = grid.getSelectedRows();

// Clear selection
grid.clearSelection();

// Listen for events
element.addEventListener('grid:selectionChanged', (e) => {
    console.log('Selected rows:', e.detail.selectedRows);
});

element.addEventListener('grid:sort', (e) => {
    console.log('Sort by:', e.detail.column, e.detail.direction);
});

element.addEventListener('grid:rowActivated', (e) => {
    console.log('Row activated:', e.detail.rowId);
});
```

### Form Validation

```javascript
// Add data-validate attribute to form
<form data-validate>
    <!-- form fields -->
</form>

// Or manual initialization
const validator = new EnterpriseUI.FormValidator(formElement);

// Validate form
const isValid = validator.validate();

// Validate single field
const isFieldValid = validator.validateField(inputElement);
```

### Toast Manager

```javascript
// Show toast
const toast = EnterpriseToast.show({
    type: 'success', // success, warning, danger, info
    title: 'Toast Title',
    message: 'Toast message',
    duration: 5000 // milliseconds, 0 for no auto-dismiss
});

// Dismiss toast
EnterpriseToast.dismiss(toast);
```

## Customization

### CSS Custom Properties

Override theme variables by defining them in your own CSS:

```css
:root {
    /* Primary Colors */
    --enterprise-bg-primary: #fafafa;
    --enterprise-bg-secondary: #f0f0f0;
    --enterprise-accent: #0066cc;
    
    /* Status Colors */
    --enterprise-success: #28a745;
    --enterprise-warning: #ffc107;
    --enterprise-danger: #dc3545;
    
    /* Spacing */
    --enterprise-space-1: 4px;
    --enterprise-space-2: 8px;
    --enterprise-space-3: 16px;
    --enterprise-space-4: 24px;
    
    /* Typography */
    --enterprise-font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    --enterprise-font-size-base: 14px;
}
```

### Utility Classes

```html
<!-- Spacing -->
<div class="enterprise-mt-3 enterprise-mb-4">Margin top 16px, bottom 24px</div>

<!-- Text Color -->
<span class="enterprise-text-primary">Primary text</span>
<span class="enterprise-text-secondary">Secondary text</span>
<span class="enterprise-text-muted">Muted text</span>

<!-- Text Alignment -->
<div class="enterprise-text-center">Centered text</div>
<div class="enterprise-text-right">Right-aligned text</div>

<!-- Display -->
<div class="enterprise-d-flex enterprise-align-items-center enterprise-gap-2">
    Flex container with centered items
</div>
```

## Keyboard Shortcuts

| Key Combination | Action |
|----------------|--------|
| `Ctrl+K` / `Cmd+K` | Open global search |
| `Ctrl+S` / `Cmd+S` | Save form/changes |
| `Esc` | Close modal or cancel |
| `?` | Show shortcuts help |
| `Tab` | Navigate forward |
| `Shift+Tab` | Navigate backward |
| `↑` / `↓` | Navigate grid rows |
| `Enter` | Activate/submit |
| `Space` | Toggle selection |

## Accessibility

The Enterprise UI Kit is designed to meet WCAG 2.1 Level AA standards:

- **Color Contrast**: All text meets minimum 4.5:1 ratio
- **Keyboard Navigation**: All interactive elements accessible via keyboard
- **Focus Indicators**: Visible focus states on all elements
- **Screen Readers**: Proper ARIA labels and semantic HTML
- **Form Labels**: All inputs have associated labels
- **Alt Text**: Meaningful descriptions for all images

## Browser Support

| Browser | Minimum Version |
|---------|----------------|
| Chrome | 90+ |
| Edge | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| iOS Safari | 14+ |
| Chrome Android | 90+ |

**Not Supported**: Internet Explorer (all versions)

## Performance

- **CSS File Size**: ~40KB (gzipped)
- **JavaScript File Size**: ~10KB (gzipped)
- **First Contentful Paint**: < 1.2s
- **Time to Interactive**: < 2.5s
- **No External Dependencies**: Except Bootstrap (foundation)

## File Structure

```
kit-enterprise/
├── assets/
│   ├── css/
│   │   ├── kit-enterprise.css
│   │   └── kit-enterprise.min.css
│   └── js/
│       ├── kit-enterprise.js
│       └── kit-enterprise.min.js
├── dashboard.html          # Complete dashboard example
├── components.html         # Component showcase
└── README.md              # This file
```

## Integration with PHP Backend

The Enterprise UI Kit is designed to integrate seamlessly with PHP MVC applications:

### Controller Example

```php
<?php
namespace App\YourModule\Controller;

class DashboardController extends AbstractController
{
    public function indexAction()
    {
        $tasks = $this->taskModel->getRecentTasks(25);
        $metrics = $this->taskModel->getMetrics();
        
        $this->view->assign('tasks', $tasks);
        $this->view->assign('metrics', $metrics);
        
        return $this->view->render('dashboard/index.phtml');
    }
}
```

### View Template Example

```php
<!-- dashboard/index.phtml -->
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <link href="/html/kit-enterprise/assets/css/kit-enterprise.css" rel="stylesheet">
</head>
<body>
    <div class="enterprise-layout">
        <!-- Layout structure -->
        
        <div class="enterprise-metrics">
            <div class="enterprise-metric-card">
                <div class="enterprise-metric-card__label">Total Tasks</div>
                <div class="enterprise-metric-card__value"><?= $metrics['total'] ?></div>
            </div>
        </div>
        
        <div class="enterprise-grid">
            <table class="enterprise-grid__table">
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr class="enterprise-grid__row" data-row-id="<?= $task['id'] ?>">
                        <td><?= htmlspecialchars($task['title']) ?></td>
                        <td>
                            <span class="enterprise-badge enterprise-badge--<?= $task['status_class'] ?>">
                                <?= $task['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="/html/kit-enterprise/assets/js/kit-enterprise.js"></script>
</body>
</html>
```

## License

Enterprise UI Kit is provided as-is for use in your projects.

## Support

For issues, questions, or contributions, please refer to your project's internal documentation or contact your development team.

---

**Version**: 1.0.0  
**Last Updated**: October 15, 2025  
**Designed for**: Business Applications, Enterprise Systems, Internal Tools
