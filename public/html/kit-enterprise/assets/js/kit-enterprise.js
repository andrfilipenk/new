/**
 * Enterprise UI Kit - JavaScript
 * Core functionality and interactions
 * Version: 1.0.0
 */

(function() {
  'use strict';

  // ============================================================
  // CONFIGURATION
  // ============================================================

  const CONFIG = {
    DEBOUNCE_DELAY: 300,
    THROTTLE_DELAY: 250,
    TOAST_DURATION: 5000,
    STORAGE_PREFIX: 'enterprise-ui-',
    PAGE_SIZE_OPTIONS: [10, 25, 50, 100],
    DEFAULT_PAGE_SIZE: 25
  };

  // ============================================================
  // UTILITY FUNCTIONS
  // ============================================================

  /**
   * Debounce function execution
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Throttle function execution
   */
  function throttle(func, wait) {
    let waiting = false;
    return function executedFunction(...args) {
      if (!waiting) {
        func(...args);
        waiting = true;
        setTimeout(() => {
          waiting = false;
        }, wait);
      }
    };
  }

  /**
   * Get item from localStorage
   */
  function getStorage(key) {
    try {
      const item = localStorage.getItem(CONFIG.STORAGE_PREFIX + key);
      return item ? JSON.parse(item) : null;
    } catch (e) {
      console.error('Error reading from localStorage:', e);
      return null;
    }
  }

  /**
   * Set item in localStorage
   */
  function setStorage(key, value) {
    try {
      localStorage.setItem(CONFIG.STORAGE_PREFIX + key, JSON.stringify(value));
      return true;
    } catch (e) {
      console.error('Error writing to localStorage:', e);
      return false;
    }
  }

  /**
   * Remove item from localStorage
   */
  function removeStorage(key) {
    try {
      localStorage.removeItem(CONFIG.STORAGE_PREFIX + key);
      return true;
    } catch (e) {
      console.error('Error removing from localStorage:', e);
      return false;
    }
  }

  // ============================================================
  // SIDEBAR COMPONENT
  // ============================================================

  class Sidebar {
    constructor(element) {
      this.element = element;
      this.isCollapsed = getStorage('sidebar.collapsed') || false;
      this.init();
    }

    init() {
      // Restore saved state
      if (this.isCollapsed) {
        this.element.classList.add('enterprise-sidebar--collapsed');
      }

      // Find toggle button
      const toggleBtn = document.querySelector('[data-sidebar-toggle]');
      if (toggleBtn) {
        toggleBtn.addEventListener('click', () => this.toggle());
      }
    }

    toggle() {
      this.isCollapsed = !this.isCollapsed;
      this.element.classList.toggle('enterprise-sidebar--collapsed');
      setStorage('sidebar.collapsed', this.isCollapsed);
      
      // Dispatch event for other components to react
      window.dispatchEvent(new CustomEvent('sidebar:toggled', {
        detail: { collapsed: this.isCollapsed }
      }));
    }

    collapse() {
      if (!this.isCollapsed) this.toggle();
    }

    expand() {
      if (this.isCollapsed) this.toggle();
    }
  }

  // ============================================================
  // DATA GRID COMPONENT
  // ============================================================

  class DataGrid {
    constructor(element) {
      this.element = element;
      this.table = element.querySelector('.enterprise-grid__table');
      this.tbody = element.querySelector('.enterprise-grid__body');
      this.selectedRows = new Set();
      this.sortColumn = null;
      this.sortDirection = 'asc';
      this.init();
    }

    init() {
      this.initSort();
      this.initSelection();
      this.initRowActions();
    }

    initSort() {
      const headers = this.element.querySelectorAll('.enterprise-grid__header-cell--sortable');
      headers.forEach(header => {
        header.addEventListener('click', () => {
          const column = header.dataset.column;
          this.sort(column);
        });
      });
    }

    sort(column) {
      // Toggle direction if same column
      if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
      }

      // Update UI indicators
      const headers = this.element.querySelectorAll('.enterprise-grid__header-cell--sortable');
      headers.forEach(header => {
        const icon = header.querySelector('.enterprise-grid__sort-icon');
        if (icon) {
          if (header.dataset.column === column) {
            icon.classList.add('enterprise-grid__sort-icon--active');
            icon.textContent = this.sortDirection === 'asc' ? '▲' : '▼';
          } else {
            icon.classList.remove('enterprise-grid__sort-icon--active');
            icon.textContent = '▲';
          }
        }
      });

      // Dispatch sort event
      this.element.dispatchEvent(new CustomEvent('grid:sort', {
        detail: { column: this.sortColumn, direction: this.sortDirection }
      }));
    }

    initSelection() {
      // Select all checkbox
      const selectAllCheckbox = this.element.querySelector('.enterprise-grid__header-cell--checkbox input[type="checkbox"]');
      if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
          this.selectAll(e.target.checked);
        });
      }

      // Individual row checkboxes
      const rowCheckboxes = this.element.querySelectorAll('.enterprise-grid__cell--checkbox input[type="checkbox"]');
      rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
          const row = e.target.closest('.enterprise-grid__row');
          this.selectRow(row, e.target.checked);
        });
      });
    }

    selectRow(row, selected) {
      const rowId = row.dataset.rowId;
      if (selected) {
        row.classList.add('enterprise-grid__row--selected');
        this.selectedRows.add(rowId);
      } else {
        row.classList.remove('enterprise-grid__row--selected');
        this.selectedRows.delete(rowId);
      }

      this.updateSelectionState();
    }

    selectAll(selected) {
      const rows = this.element.querySelectorAll('.enterprise-grid__row');
      rows.forEach(row => {
        const checkbox = row.querySelector('.enterprise-grid__cell--checkbox input[type="checkbox"]');
        if (checkbox) {
          checkbox.checked = selected;
          this.selectRow(row, selected);
        }
      });
    }

    updateSelectionState() {
      // Dispatch selection event
      this.element.dispatchEvent(new CustomEvent('grid:selectionChanged', {
        detail: { selectedRows: Array.from(this.selectedRows) }
      }));
    }

    initRowActions() {
      // Double-click to open details
      const rows = this.element.querySelectorAll('.enterprise-grid__row');
      rows.forEach(row => {
        row.addEventListener('dblclick', () => {
          const rowId = row.dataset.rowId;
          this.element.dispatchEvent(new CustomEvent('grid:rowActivated', {
            detail: { rowId }
          }));
        });
      });
    }

    getSelectedRows() {
      return Array.from(this.selectedRows);
    }

    clearSelection() {
      this.selectAll(false);
    }
  }

  // ============================================================
  // MODAL COMPONENT
  // ============================================================

  class Modal {
    constructor(element) {
      this.element = element;
      this.backdrop = element.querySelector('.enterprise-modal__backdrop');
      this.container = element.querySelector('.enterprise-modal__container');
      this.closeBtn = element.querySelector('.enterprise-modal__close');
      this.isOpen = false;
      this.focusTrap = null;
      this.previousActiveElement = null;
      this.init();
    }

    init() {
      // Close button
      if (this.closeBtn) {
        this.closeBtn.addEventListener('click', () => this.close());
      }

      // Backdrop click
      if (this.backdrop) {
        this.backdrop.addEventListener('click', () => {
          if (this.element.dataset.backdropClose !== 'false') {
            this.close();
          }
        });
      }

      // Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.isOpen) {
          this.close();
        }
      });
    }

    open() {
      this.previousActiveElement = document.activeElement;
      this.element.classList.add('enterprise-modal--active');
      this.isOpen = true;
      document.body.style.overflow = 'hidden';
      
      // Focus first focusable element
      setTimeout(() => {
        const focusable = this.container.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable) focusable.focus();
      }, 100);

      this.element.dispatchEvent(new CustomEvent('modal:opened'));
    }

    close() {
      this.element.classList.remove('enterprise-modal--active');
      this.isOpen = false;
      document.body.style.overflow = '';
      
      // Return focus
      if (this.previousActiveElement) {
        this.previousActiveElement.focus();
      }

      this.element.dispatchEvent(new CustomEvent('modal:closed'));
    }
  }

  // ============================================================
  // TOAST NOTIFICATION
  // ============================================================

  class ToastManager {
    constructor() {
      this.container = null;
      this.toasts = [];
      this.init();
    }

    init() {
      // Create container if it doesn't exist
      this.container = document.querySelector('.enterprise-toast-container');
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'enterprise-toast-container';
        document.body.appendChild(this.container);
      }
    }

    show(options) {
      const {
        type = 'info',
        title = '',
        message = '',
        duration = CONFIG.TOAST_DURATION
      } = options;

      const toast = document.createElement('div');
      toast.className = `enterprise-toast enterprise-toast--${type}`;
      
      const iconMap = {
        success: '✓',
        warning: '⚠',
        danger: '✕',
        info: 'ⓘ'
      };

      toast.innerHTML = `
        <div class="enterprise-toast__icon">${iconMap[type] || 'ⓘ'}</div>
        <div class="enterprise-toast__content">
          ${title ? `<div class="enterprise-toast__title">${title}</div>` : ''}
          ${message ? `<div class="enterprise-toast__message">${message}</div>` : ''}
        </div>
        <button class="enterprise-toast__close" aria-label="Close">×</button>
      `;

      const closeBtn = toast.querySelector('.enterprise-toast__close');
      closeBtn.addEventListener('click', () => this.dismiss(toast));

      this.container.appendChild(toast);
      this.toasts.push(toast);

      // Auto-dismiss
      if (duration > 0) {
        setTimeout(() => this.dismiss(toast), duration);
      }

      return toast;
    }

    dismiss(toast) {
      toast.style.animation = 'slideOutRight 200ms ease';
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
        this.toasts = this.toasts.filter(t => t !== toast);
      }, 200);
    }

    success(title, message, duration) {
      return this.show({ type: 'success', title, message, duration });
    }

    warning(title, message, duration) {
      return this.show({ type: 'warning', title, message, duration });
    }

    danger(title, message, duration) {
      return this.show({ type: 'danger', title, message, duration });
    }

    info(title, message, duration) {
      return this.show({ type: 'info', title, message, duration });
    }
  }

  // ============================================================
  // TAB NAVIGATION
  // ============================================================

  class Tabs {
    constructor(element) {
      this.element = element;
      this.tabs = element.querySelectorAll('.enterprise-tab');
      this.contents = document.querySelectorAll('.enterprise-tab-content');
      this.activeTab = null;
      this.init();
    }

    init() {
      this.tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
          e.preventDefault();
          this.activate(tab);
        });
      });

      // Activate first tab or tab from URL hash
      const hash = window.location.hash.substring(1);
      const initialTab = hash 
        ? Array.from(this.tabs).find(t => t.dataset.tab === hash)
        : this.tabs[0];
      
      if (initialTab) this.activate(initialTab);
    }

    activate(tab) {
      const tabId = tab.dataset.tab;

      // Update tabs
      this.tabs.forEach(t => t.classList.remove('enterprise-tab--active'));
      tab.classList.add('enterprise-tab--active');

      // Update content
      this.contents.forEach(content => {
        if (content.id === tabId) {
          content.classList.add('enterprise-tab-content--active');
        } else {
          content.classList.remove('enterprise-tab-content--active');
        }
      });

      this.activeTab = tabId;

      // Update URL hash without scrolling
      if (history.replaceState) {
        history.replaceState(null, null, '#' + tabId);
      }

      this.element.dispatchEvent(new CustomEvent('tab:changed', {
        detail: { tab: tabId }
      }));
    }
  }

  // ============================================================
  // FORM VALIDATION
  // ============================================================

  class FormValidator {
    constructor(form) {
      this.form = form;
      this.init();
    }

    init() {
      this.form.addEventListener('submit', (e) => {
        if (!this.validate()) {
          e.preventDefault();
        }
      });

      // Real-time validation
      const inputs = this.form.querySelectorAll('input, select, textarea');
      inputs.forEach(input => {
        input.addEventListener('blur', () => this.validateField(input));
        input.addEventListener('input', debounce(() => this.validateField(input), CONFIG.DEBOUNCE_DELAY));
      });
    }

    validateField(field) {
      const errors = [];

      // Required validation
      if (field.hasAttribute('required') && !field.value.trim()) {
        errors.push('This field is required');
      }

      // Email validation
      if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
          errors.push('Please enter a valid email address');
        }
      }

      // Pattern validation
      if (field.pattern && field.value) {
        const regex = new RegExp(field.pattern);
        if (!regex.test(field.value)) {
          errors.push(field.title || 'Please match the requested format');
        }
      }

      // Min/Max length
      if (field.minLength && field.value.length < field.minLength) {
        errors.push(`Minimum length is ${field.minLength} characters`);
      }
      if (field.maxLength && field.value.length > field.maxLength) {
        errors.push(`Maximum length is ${field.maxLength} characters`);
      }

      this.updateFieldState(field, errors);
      return errors.length === 0;
    }

    updateFieldState(field, errors) {
      const formGroup = field.closest('.enterprise-form-group');
      if (!formGroup) return;

      // Remove existing error messages
      const existingError = formGroup.querySelector('.enterprise-error-text');
      if (existingError) existingError.remove();

      // Update field class
      field.classList.remove('enterprise-input--error', 'enterprise-input--success');
      
      if (errors.length > 0) {
        field.classList.add('enterprise-input--error');
        const errorText = document.createElement('span');
        errorText.className = 'enterprise-error-text';
        errorText.textContent = errors[0];
        field.parentNode.appendChild(errorText);
      } else if (field.value) {
        field.classList.add('enterprise-input--success');
      }
    }

    validate() {
      const inputs = this.form.querySelectorAll('input, select, textarea');
      let isValid = true;

      inputs.forEach(input => {
        if (!this.validateField(input)) {
          isValid = false;
        }
      });

      return isValid;
    }
  }

  // ============================================================
  // SEARCH FUNCTIONALITY
  // ============================================================

  class SearchBox {
    constructor(element) {
      this.element = element;
      this.input = element.querySelector('input[type="search"], input[type="text"]');
      this.init();
    }

    init() {
      if (!this.input) return;

      this.input.addEventListener('input', debounce((e) => {
        this.search(e.target.value);
      }, CONFIG.DEBOUNCE_DELAY));
    }

    search(query) {
      this.element.dispatchEvent(new CustomEvent('search:query', {
        detail: { query }
      }));
    }
  }

  // ============================================================
  // AUTO-INITIALIZATION
  // ============================================================

  const EnterpriseUI = {
    Sidebar,
    DataGrid,
    Modal,
    ToastManager,
    Tabs,
    FormValidator,
    SearchBox,
    
    init() {
      // Initialize sidebars
      document.querySelectorAll('.enterprise-sidebar').forEach(el => {
        new Sidebar(el);
      });

      // Initialize data grids
      document.querySelectorAll('.enterprise-grid').forEach(el => {
        new DataGrid(el);
      });

      // Initialize modals
      document.querySelectorAll('.enterprise-modal').forEach(el => {
        new Modal(el);
      });

      // Initialize tabs
      document.querySelectorAll('.enterprise-tabs').forEach(el => {
        new Tabs(el);
      });

      // Initialize forms
      document.querySelectorAll('form[data-validate]').forEach(el => {
        new FormValidator(el);
      });

      // Initialize search boxes
      document.querySelectorAll('[data-search]').forEach(el => {
        new SearchBox(el);
      });

      // Create global toast manager
      window.EnterpriseToast = new ToastManager();
    }
  };

  // Auto-initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => EnterpriseUI.init());
  } else {
    EnterpriseUI.init();
  }

  // Export to global scope
  window.EnterpriseUI = EnterpriseUI;

})();
