/**
 * Reusable UI Components Library
 * Bootstrap 5 based components for forms, tables, modals, alerts, pagination, breadcrumbs
 */

const UI = {
    /**
     * Create a Bootstrap alert
     */
    createAlert(message, type = 'info', dismissible = true) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}${dismissible ? ' alert-dismissible fade show' : ''}`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = message;
        
        if (dismissible) {
            alertDiv.innerHTML += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        }
        
        return alertDiv;
    },

    /**
     * Create a Bootstrap modal
     */
    createModal(options = {}) {
        const {
            id = 'modal-' + Date.now(),
            title = 'Modal',
            body = '',
            footer = '',
            size = '', // 'sm', 'lg', 'xl'
            centered = true,
            scrollable = false,
            staticBackdrop = false
        } = options;

        const sizeClass = size ? `modal-${size}` : '';
        const centeredClass = centered ? 'modal-dialog-centered' : '';
        const scrollClass = scrollable ? 'modal-dialog-scrollable' : '';

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = id;
        modal.setAttribute('tabindex', '-1');
        if (staticBackdrop) {
            modal.setAttribute('data-bs-backdrop', 'static');
        }

        modal.innerHTML = `
            <div class="modal-dialog ${sizeClass} ${centeredClass} ${scrollClass}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">${body}</div>
                    ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return new bootstrap.Modal(modal);
    },

    /**
     * Create confirmation dialog
     */
    confirm(options = {}) {
        const {
            title = 'Confirm',
            message = 'Are you sure?',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            confirmClass = 'btn-primary',
            onConfirm = null,
            onCancel = null
        } = options;

        const footer = `
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
            <button type="button" class="btn ${confirmClass}" id="confirm-btn">${confirmText}</button>
        `;

        const modal = this.createModal({
            title,
            body: `<p>${message}</p>`,
            footer,
            size: 'sm',
            centered: true
        });

        const modalElement = document.querySelector('.modal:last-child');
        const confirmBtn = modalElement.querySelector('#confirm-btn');

        confirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            modal.hide();
        });

        if (onCancel) {
            modalElement.addEventListener('hidden.bs.modal', onCancel);
        }

        modal.show();
        return modal;
    },

    /**
     * Create pagination
     */
    createPagination(options = {}) {
        const {
            currentPage = 1,
            totalPages = 1,
            onPageChange = null,
            maxVisible = 5
        } = options;

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>`;
        ul.appendChild(prevLi);

        // Page numbers
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        // First page
        if (startPage > 1) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = '<a class="page-link" href="#" data-page="1">1</a>';
            ul.appendChild(li);

            if (startPage > 2) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = '<span class="page-link">...</span>';
                ul.appendChild(dots);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === currentPage ? 'active' : ''}`;
            li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            ul.appendChild(li);
        }

        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('li');
                dots.className = 'page-item disabled';
                dots.innerHTML = '<span class="page-link">...</span>';
                ul.appendChild(dots);
            }

            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `<a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>`;
            ul.appendChild(li);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>`;
        ul.appendChild(nextLi);

        nav.appendChild(ul);

        // Add click handlers
        if (onPageChange) {
            nav.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(link.dataset.page);
                    if (page >= 1 && page <= totalPages) {
                        onPageChange(page);
                    }
                });
            });
        }

        return nav;
    },

    /**
     * Create breadcrumbs
     */
    createBreadcrumbs(items = []) {
        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'breadcrumb');

        const ol = document.createElement('ol');
        ol.className = 'breadcrumb';

        items.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = `breadcrumb-item ${index === items.length - 1 ? 'active' : ''}`;
            
            if (index === items.length - 1) {
                li.setAttribute('aria-current', 'page');
                li.textContent = item.text;
            } else {
                li.innerHTML = `<a href="${item.url || '#'}">${item.text}</a>`;
            }
            
            ol.appendChild(li);
        });

        nav.appendChild(ol);
        return nav;
    },

    /**
     * Create data table
     */
    createTable(options = {}) {
        const {
            columns = [],
            data = [],
            striped = true,
            bordered = false,
            hover = true,
            responsive = true,
            actions = null
        } = options;

        const wrapper = document.createElement('div');
        if (responsive) {
            wrapper.className = 'table-responsive';
        }

        const table = document.createElement('table');
        const classes = ['table'];
        if (striped) classes.push('table-striped');
        if (bordered) classes.push('table-bordered');
        if (hover) classes.push('table-hover');
        table.className = classes.join(' ');

        // Create header
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        columns.forEach(col => {
            const th = document.createElement('th');
            th.textContent = col.label || col.field;
            if (col.width) th.style.width = col.width;
            headerRow.appendChild(th);
        });
        if (actions) {
            const th = document.createElement('th');
            th.textContent = 'Actions';
            th.style.width = '150px';
            headerRow.appendChild(th);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create body
        const tbody = document.createElement('tbody');
        data.forEach(row => {
            const tr = document.createElement('tr');
            
            columns.forEach(col => {
                const td = document.createElement('td');
                const value = row[col.field];
                
                if (col.render) {
                    td.innerHTML = col.render(value, row);
                } else {
                    td.textContent = value ?? '';
                }
                
                tr.appendChild(td);
            });

            if (actions) {
                const td = document.createElement('td');
                td.innerHTML = actions(row);
                tr.appendChild(td);
            }

            tbody.appendChild(tr);
        });
        table.appendChild(tbody);

        wrapper.appendChild(table);
        return wrapper;
    },

    /**
     * Create form group
     */
    createFormGroup(options = {}) {
        const {
            label = '',
            type = 'text',
            name = '',
            id = name,
            value = '',
            placeholder = '',
            required = false,
            helpText = '',
            options: selectOptions = [],
            rows = 3
        } = options;

        const formGroup = document.createElement('div');
        formGroup.className = 'mb-3';

        if (label) {
            const labelEl = document.createElement('label');
            labelEl.className = 'form-label';
            labelEl.setAttribute('for', id);
            labelEl.textContent = label;
            if (required) {
                labelEl.innerHTML += ' <span class="text-danger">*</span>';
            }
            formGroup.appendChild(labelEl);
        }

        let input;
        if (type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = rows;
        } else if (type === 'select') {
            input = document.createElement('select');
            selectOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value ?? opt;
                option.textContent = opt.label ?? opt;
                if (opt.value === value) option.selected = true;
                input.appendChild(option);
            });
        } else {
            input = document.createElement('input');
            input.type = type;
            input.value = value;
        }

        input.className = 'form-control';
        input.id = id;
        input.name = name;
        if (placeholder) input.placeholder = placeholder;
        if (required) input.required = true;

        formGroup.appendChild(input);

        if (helpText) {
            const help = document.createElement('div');
            help.className = 'form-text';
            help.textContent = helpText;
            formGroup.appendChild(help);
        }

        return formGroup;
    },

    /**
     * Create spinner/loader
     */
    createSpinner(options = {}) {
        const {
            type = 'border', // 'border' or 'grow'
            size = '', // 'sm'
            color = 'primary',
            text = ''
        } = options;

        const spinner = document.createElement('div');
        spinner.className = `spinner-${type}${size ? ' spinner-' + type + '-' + size : ''} text-${color}`;
        spinner.setAttribute('role', 'status');

        if (text) {
            const span = document.createElement('span');
            span.className = 'visually-hidden';
            span.textContent = text;
            spinner.appendChild(span);
        }

        return spinner;
    },

    /**
     * Create badge
     */
    createBadge(text, variant = 'primary', pill = false) {
        const badge = document.createElement('span');
        badge.className = `badge bg-${variant}${pill ? ' rounded-pill' : ''}`;
        badge.textContent = text;
        return badge;
    },

    /**
     * Create button
     */
    createButton(options = {}) {
        const {
            text = 'Button',
            variant = 'primary',
            size = '', // 'sm', 'lg'
            outline = false,
            disabled = false,
            type = 'button',
            icon = '',
            onClick = null
        } = options;

        const btn = document.createElement('button');
        btn.type = type;
        
        const classes = ['btn'];
        classes.push(outline ? `btn-outline-${variant}` : `btn-${variant}`);
        if (size) classes.push(`btn-${size}`);
        btn.className = classes.join(' ');
        
        if (disabled) btn.disabled = true;
        
        if (icon) {
            btn.innerHTML = `<i class="${icon}"></i> ${text}`;
        } else {
            btn.textContent = text;
        }
        
        if (onClick) {
            btn.addEventListener('click', onClick);
        }
        
        return btn;
    }
};

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UI;
}
