/**
 * Kit Professional - Interactive Functionality
 * Handles all dynamic behavior and user interactions
 */

(function() {
  'use strict';

  // ========================================
  // SIDEBAR NAVIGATION
  // ========================================
  
  function initSidebar() {
    const sidebar = document.querySelector('.pro-sidebar');
    const toggleBtn = document.querySelector('.pro-sidebar-toggle');
    const mainContent = document.querySelector('.pro-main');
    
    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        
        // Store preference
        const isOpen = sidebar.classList.contains('open');
        localStorage.setItem('sidebarOpen', isOpen);
      });
      
      // Restore sidebar state
      const savedState = localStorage.getItem('sidebarOpen');
      if (savedState === 'true') {
        sidebar.classList.add('open');
      }
    }
    
    // Active link highlighting
    const sidebarLinks = document.querySelectorAll('.pro-sidebar-link');
    sidebarLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        sidebarLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');
      });
    });
  }

  // ========================================
  // TOAST NOTIFICATIONS
  // ========================================
  
  const ToastManager = {
    container: null,
    
    init() {
      if (!this.container) {
        this.container = document.createElement('div');
        this.container.className = 'pro-toast-container';
        document.body.appendChild(this.container);
      }
    },
    
    show(options) {
      this.init();
      
      const {
        type = 'info',
        title = '',
        message = '',
        duration = 5000
      } = options;
      
      const toast = document.createElement('div');
      toast.className = `pro-toast ${type}`;
      
      const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
      };
      
      toast.innerHTML = `
        <i class="pro-toast-icon bi ${iconMap[type]}"></i>
        <div class="pro-toast-content">
          ${title ? `<div class="pro-toast-title">${title}</div>` : ''}
          <div class="pro-toast-message">${message}</div>
        </div>
        <button class="pro-toast-close" aria-label="Close">
          <i class="bi bi-x"></i>
        </button>
      `;
      
      this.container.appendChild(toast);
      
      // Close button handler
      const closeBtn = toast.querySelector('.pro-toast-close');
      closeBtn.addEventListener('click', () => {
        this.remove(toast);
      });
      
      // Auto-dismiss
      if (duration > 0) {
        setTimeout(() => {
          this.remove(toast);
        }, duration);
      }
      
      return toast;
    },
    
    remove(toast) {
      toast.style.animation = 'slideOut 0.3s ease-in';
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    },
    
    success(message, title = 'Success') {
      return this.show({ type: 'success', title, message });
    },
    
    error(message, title = 'Error') {
      return this.show({ type: 'error', title, message });
    },
    
    warning(message, title = 'Warning') {
      return this.show({ type: 'warning', title, message });
    },
    
    info(message, title = '') {
      return this.show({ type: 'info', title, message });
    }
  };

  // Add slideOut animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideOut {
      from {
        transform: translateX(0);
        opacity: 1;
      }
      to {
        transform: translateX(400px);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(style);

  // ========================================
  // MODAL DIALOGS
  // ========================================
  
  const ModalManager = {
    activeModals: [],
    
    open(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      
      modal.style.display = 'flex';
      this.activeModals.push(modal);
      document.body.style.overflow = 'hidden';
      
      // Focus trap
      this.setupFocusTrap(modal);
      
      // ESC key handler
      const escHandler = (e) => {
        if (e.key === 'Escape') {
          this.close(modalId);
        }
      };
      modal._escHandler = escHandler;
      document.addEventListener('keydown', escHandler);
    },
    
    close(modalId) {
      const modal = document.getElementById(modalId);
      if (!modal) return;
      
      modal.style.display = 'none';
      
      const index = this.activeModals.indexOf(modal);
      if (index > -1) {
        this.activeModals.splice(index, 1);
      }
      
      if (this.activeModals.length === 0) {
        document.body.style.overflow = '';
      }
      
      // Remove ESC handler
      if (modal._escHandler) {
        document.removeEventListener('keydown', modal._escHandler);
      }
    },
    
    setupFocusTrap(modal) {
      const focusableElements = modal.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];
      
      modal.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
          if (e.shiftKey) {
            if (document.activeElement === firstElement) {
              lastElement.focus();
              e.preventDefault();
            }
          } else {
            if (document.activeElement === lastElement) {
              firstElement.focus();
              e.preventDefault();
            }
          }
        }
      });
      
      // Focus first element
      if (firstElement) {
        setTimeout(() => firstElement.focus(), 100);
      }
    }
  };
  
  // Auto-init modal close buttons
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('pro-modal-backdrop')) {
      const modal = e.target.closest('[id]');
      if (modal) {
        ModalManager.close(modal.id);
      }
    }
    
    if (e.target.closest('.pro-modal-close')) {
      const modal = e.target.closest('[id]');
      if (modal) {
        ModalManager.close(modal.id);
      }
    }
  });

  // ========================================
  // TASK FILTERS
  // ========================================
  
  function initTaskFilters() {
    const filterChips = document.querySelectorAll('.pro-filter-chip');
    
    filterChips.forEach(chip => {
      chip.addEventListener('click', function() {
        const filter = this.dataset.filter;
        
        // Update active state
        filterChips.forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        
        // Filter tasks
        filterTasks(filter);
      });
    });
  }
  
  function filterTasks(filter) {
    const tasks = document.querySelectorAll('.pro-task-item');
    
    tasks.forEach(task => {
      if (filter === 'all') {
        task.style.display = 'flex';
      } else {
        const matchesFilter = task.dataset.filter === filter;
        task.style.display = matchesFilter ? 'flex' : 'none';
      }
    });
  }

  // ========================================
  // DATA TABLE FEATURES
  // ========================================
  
  function initDataTables() {
    // Sortable columns
    const sortableHeaders = document.querySelectorAll('.pro-table th.sortable');
    
    sortableHeaders.forEach(header => {
      header.addEventListener('click', function() {
        const table = this.closest('.pro-table');
        const columnIndex = Array.from(this.parentNode.children).indexOf(this);
        const currentOrder = this.dataset.order || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // Update sort indicators
        sortableHeaders.forEach(h => {
          h.classList.remove('sorted-asc', 'sorted-desc');
          delete h.dataset.order;
        });
        
        this.classList.add(`sorted-${newOrder}`);
        this.dataset.order = newOrder;
        
        // Sort table rows
        sortTable(table, columnIndex, newOrder);
      });
    });
    
    // Row selection
    const masterCheckbox = document.querySelector('.pro-table thead input[type="checkbox"]');
    const rowCheckboxes = document.querySelectorAll('.pro-table tbody input[type="checkbox"]');
    
    if (masterCheckbox) {
      masterCheckbox.addEventListener('change', function() {
        rowCheckboxes.forEach(cb => {
          cb.checked = this.checked;
        });
        updateBulkActions();
      });
    }
    
    rowCheckboxes.forEach(cb => {
      cb.addEventListener('change', updateBulkActions);
    });
  }
  
  function sortTable(table, columnIndex, order) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
      const aValue = a.cells[columnIndex].textContent.trim();
      const bValue = b.cells[columnIndex].textContent.trim();
      
      const comparison = aValue.localeCompare(bValue, undefined, { numeric: true });
      return order === 'asc' ? comparison : -comparison;
    });
    
    rows.forEach(row => tbody.appendChild(row));
  }
  
  function updateBulkActions() {
    const checked = document.querySelectorAll('.pro-table tbody input[type="checkbox"]:checked');
    const bulkActions = document.querySelector('.pro-bulk-actions');
    
    if (bulkActions) {
      if (checked.length > 0) {
        bulkActions.style.display = 'flex';
        bulkActions.querySelector('.selection-count').textContent = `${checked.length} selected`;
      } else {
        bulkActions.style.display = 'none';
      }
    }
  }

  // ========================================
  // SEARCH FUNCTIONALITY
  // ========================================
  
  function initSearch() {
    const searchInput = document.querySelector('.pro-search input');
    
    if (searchInput) {
      // Keyboard shortcut (Ctrl+K)
      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          e.preventDefault();
          searchInput.focus();
        }
      });
      
      // Search functionality
      let searchTimeout;
      searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          performSearch(this.value);
        }, 300);
      });
    }
  }
  
  function performSearch(query) {
    console.log('Searching for:', query);
    // Implement actual search logic here
    // This would typically make an API call
  }

  // ========================================
  // FORM VALIDATION
  // ========================================
  
  function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        
        inputs.forEach(input => {
          if (!validateInput(input)) {
            isValid = false;
          }
        });
        
        if (isValid) {
          // Submit form
          this.submit();
        }
      });
      
      // Real-time validation
      const inputs = form.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.addEventListener('blur', () => validateInput(input));
        input.addEventListener('input', () => {
          if (input.classList.contains('error')) {
            validateInput(input);
          }
        });
      });
    });
  }
  
  function validateInput(input) {
    const formGroup = input.closest('.pro-form-group');
    let errorElement = formGroup.querySelector('.pro-form-error');
    
    // Remove existing error
    if (errorElement) {
      errorElement.remove();
    }
    input.classList.remove('error');
    
    // Check validity
    let errorMessage = '';
    
    if (input.hasAttribute('required') && !input.value.trim()) {
      errorMessage = 'This field is required';
    } else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
      errorMessage = 'Please enter a valid email address';
    } else if (input.minLength && input.value.length < input.minLength) {
      errorMessage = `Minimum ${input.minLength} characters required`;
    }
    
    if (errorMessage) {
      input.classList.add('error');
      errorElement = document.createElement('div');
      errorElement.className = 'pro-form-error';
      errorElement.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${errorMessage}`;
      formGroup.appendChild(errorElement);
      return false;
    }
    
    return true;
  }
  
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // ========================================
  // INITIALIZATION
  // ========================================
  
  function init() {
    initSidebar();
    initTaskFilters();
    initDataTables();
    initSearch();
    initFormValidation();
    
    console.log('Kit Professional initialized');
  }
  
  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  // Expose public API
  window.KitProfessional = {
    toast: ToastManager,
    modal: ModalManager
  };

})();
