/**
 * Kanban Board JavaScript Implementation
 * Handles drag-and-drop functionality, API communication, and UI interactions
 */
class KanbanBoard {
    constructor(options) {
        this.container = document.querySelector(options.container);
        this.apiBaseUrl = options.apiBaseUrl;
        this.csrfToken = options.csrfToken;
        
        this.boardData = null;
        this.draggedTask = null;
        this.draggedFrom = null;
        this.dragOverColumn = null;
        
        this.init = this.init.bind(this);
        this.loadBoardData = this.loadBoardData.bind(this);
        this.renderBoard = this.renderBoard.bind(this);
        this.setupEventListeners = this.setupEventListeners.bind(this);
        this.handleDragStart = this.handleDragStart.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        this.moveTask = this.moveTask.bind(this);
    }

    async init() {
        this.showLoading();
        try {
            await this.loadBoardData();
            this.renderBoard();
            this.setupEventListeners();
            this.hideLoading();
        } catch (error) {
            this.hideLoading();
            this.showError('Failed to initialize kanban board: ' + error.message);
        }
    }

    async loadBoardData(filters = {}) {
        const queryParams = new URLSearchParams(filters).toString();
        const url = `${this.apiBaseUrl}/board${queryParams ? '?' + queryParams : ''}`;
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        this.boardData = await response.json();
    }

    renderBoard() {
        if (!this.boardData || !this.boardData.statuses) {
            this.container.innerHTML = '<div class="alert alert-warning">No board data available</div>';
            return;
        }

        this.container.innerHTML = '';

        this.boardData.statuses.forEach(status => {
            const column = this.createColumn(status);
            this.container.appendChild(column);
        });
    }

    createColumn(status) {
        const column = document.createElement('div');
        column.className = 'kanban-column';
        column.dataset.statusId = status.id;

        const tasks = this.boardData.tasks[status.id] || [];
        
        column.innerHTML = `
            <div class="kanban-column-header">
                <span class="status-indicator status-${status.color}"></span>
                <span class="column-title">${status.title}</span>
                <span class="badge bg-secondary">${tasks.length}</span>
            </div>
            <div class="kanban-column-body" data-status-id="${status.id}">
                ${tasks.map(task => this.createTaskCard(task)).join('')}
            </div>
        `;

        // Setup drag and drop for column
        const columnBody = column.querySelector('.kanban-column-body');
        columnBody.addEventListener('dragover', this.handleDragOver);
        columnBody.addEventListener('drop', this.handleDrop);
        columnBody.addEventListener('dragenter', this.handleDragEnter);
        columnBody.addEventListener('dragleave', this.handleDragLeave);

        return column;
    }

    createTaskCard(task) {
        const priorityClass = `priority-${task.priority.color}`;
        const assignedUser = task.assigned ? task.assigned.name : 'Unassigned';
        const dueDate = task.due_date ? new Date(task.due_date).toLocaleDateString() : '';

        return `
            <div class="kanban-task" 
                 draggable="true" 
                 data-task-id="${task.id}"
                 data-position="${task.position}">
                <div class="kanban-task-title">${this.escapeHtml(task.title)}</div>
                ${task.description ? `<div class="kanban-task-description text-muted small">${this.escapeHtml(task.description)}</div>` : ''}
                <div class="kanban-task-meta">
                    <div class="kanban-task-assignee">
                        <img src="/avatars/default.png" alt="${assignedUser}" title="${assignedUser}">
                        <span>${assignedUser}</span>
                    </div>
                    <span class="kanban-task-priority ${priorityClass}">${task.priority.title}</span>
                </div>
                ${dueDate ? `<div class="kanban-task-due-date">Due: ${dueDate}</div>` : ''}
            </div>
        `;
    }

    setupEventListeners() {
        // Task drag events
        this.container.addEventListener('dragstart', this.handleDragStart);
        this.container.addEventListener('dragend', this.handleDragEnd.bind(this));

        // Task click events for details
        this.container.addEventListener('click', (e) => {
            const task = e.target.closest('.kanban-task');
            if (task && !this.draggedTask) {
                this.showTaskDetails(task.dataset.taskId);
            }
        });

        // Filter events
        const applyFiltersBtn = document.getElementById('applyFilters');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const addNewTaskBtn = document.getElementById('addNewTask');

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', this.applyFilters.bind(this));
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', this.clearFilters.bind(this));
        }

        if (addNewTaskBtn) {
            addNewTaskBtn.addEventListener('click', this.showTaskForm.bind(this));
        }

        // Task form events
        const saveTaskBtn = document.getElementById('saveTaskBtn');
        if (saveTaskBtn) {
            saveTaskBtn.addEventListener('click', this.saveTask.bind(this));
        }
    }

    handleDragStart(e) {
        const task = e.target.closest('.kanban-task');
        if (!task) return;

        this.draggedTask = task;
        this.draggedFrom = task.closest('.kanban-column-body');
        
        task.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', task.outerHTML);
    }

    handleDragEnd(e) {
        if (this.draggedTask) {
            this.draggedTask.classList.remove('dragging');
            this.draggedTask = null;
            this.draggedFrom = null;
        }

        // Remove all drop zone indicators
        document.querySelectorAll('.drop-zone').forEach(zone => zone.remove());
        document.querySelectorAll('.kanban-column-body').forEach(body => {
            body.classList.remove('drag-over');
        });
    }

    handleDragEnter(e) {
        e.preventDefault();
        const columnBody = e.target.closest('.kanban-column-body');
        if (columnBody && columnBody !== this.draggedFrom) {
            columnBody.classList.add('drag-over');
        }
    }

    handleDragLeave(e) {
        e.preventDefault();
        const columnBody = e.target.closest('.kanban-column-body');
        if (columnBody && !columnBody.contains(e.relatedTarget)) {
            columnBody.classList.remove('drag-over');
        }
    }

    handleDragOver(e) {
        e.preventDefault();
        const columnBody = e.target.closest('.kanban-column-body');
        if (!columnBody || !this.draggedTask) return;

        e.dataTransfer.dropEffect = 'move';

        // Calculate drop position
        const tasks = Array.from(columnBody.querySelectorAll('.kanban-task:not(.dragging)'));
        const afterElement = this.getDragAfterElement(columnBody, e.clientY);
        
        // Remove existing drop zone
        const existingDropZone = columnBody.querySelector('.drop-zone');
        if (existingDropZone) {
            existingDropZone.remove();
        }

        // Add drop zone indicator
        const dropZone = document.createElement('div');
        dropZone.className = 'drop-zone';
        dropZone.innerHTML = 'Drop task here';

        if (afterElement == null) {
            columnBody.appendChild(dropZone);
        } else {
            columnBody.insertBefore(dropZone, afterElement);
        }
    }

    handleDrop(e) {
        e.preventDefault();
        const columnBody = e.target.closest('.kanban-column-body');
        if (!columnBody || !this.draggedTask) return;

        const newStatusId = parseInt(columnBody.dataset.statusId);
        const oldStatusId = parseInt(this.draggedFrom.dataset.statusId);

        // Calculate new position
        const dropZone = columnBody.querySelector('.drop-zone');
        let newPosition = 0;
        
        if (dropZone) {
            const tasks = Array.from(columnBody.querySelectorAll('.kanban-task:not(.dragging)'));
            newPosition = Array.from(columnBody.children).indexOf(dropZone);
            dropZone.remove();
        }

        // Move the task
        this.moveTask(
            parseInt(this.draggedTask.dataset.taskId),
            newStatusId,
            newPosition
        );
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.kanban-task:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    async moveTask(taskId, statusId, position) {
        this.showLoading();
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/task/${taskId}/move`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    status_id: statusId,
                    position: position,
                    _token: this.csrfToken
                })
            });

            const result = await response.json();

            if (result.success) {
                // Reload board data to reflect changes
                await this.loadBoardData();
                this.renderBoard();
                this.setupEventListeners();
                this.showSuccess('Task moved successfully');
            } else {
                this.showError(result.error || 'Failed to move task');
                // Reload to revert UI changes
                await this.loadBoardData();
                this.renderBoard();
                this.setupEventListeners();
            }
        } catch (error) {
            this.showError('Failed to move task: ' + error.message);
            // Reload to revert UI changes
            await this.loadBoardData();
            this.renderBoard();
            this.setupEventListeners();
        } finally {
            this.hideLoading();
        }
    }

    async showTaskDetails(taskId) {
        this.showLoading();
        
        try {
            const response = await fetch(`${this.apiBaseUrl}/task/${taskId}/details`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                this.renderTaskDetails(result.task, result.logs);
                const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
                modal.show();
            } else {
                this.showError(result.error || 'Failed to load task details');
            }
        } catch (error) {
            this.showError('Failed to load task details: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    renderTaskDetails(task, logs) {
        const content = document.getElementById('taskDetailContent');
        const assignedUser = task.assigned ? task.assigned.name : 'Unassigned';
        const createdBy = task.creator ? task.creator.name : 'Unknown';

        content.innerHTML = `
            <div class="row">
                <div class="col-md-8">
                    <h4>${this.escapeHtml(task.title)}</h4>
                    ${task.description ? `<p class="text-muted">${this.escapeHtml(task.description)}</p>` : ''}
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="badge bg-${task.status.color}">${task.status.title}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong> 
                            <span class="badge bg-${task.priority.color}">${task.priority.title}</span>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Assigned to:</strong> ${assignedUser}
                        </div>
                        <div class="col-md-6">
                            <strong>Created by:</strong> ${createdBy}
                        </div>
                    </div>
                    
                    ${task.begin_date || task.end_date ? `
                        <div class="row mt-2">
                            ${task.begin_date ? `<div class="col-md-6"><strong>Start:</strong> ${task.begin_date}</div>` : ''}
                            ${task.end_date ? `<div class="col-md-6"><strong>Due:</strong> ${task.end_date}</div>` : ''}
                        </div>
                    ` : ''}
                </div>
                <div class="col-md-4">
                    <h6>Recent Activity</h6>
                    <div class="activity-log" style="max-height: 200px; overflow-y: auto;">
                        ${logs.map(log => `
                            <div class="small text-muted mb-2">
                                <div>${this.escapeHtml(log.content)}</div>
                                <div class="text-xs">${new Date(log.created_at).toLocaleString()}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    showTaskForm(taskData = null) {
        const modal = new bootstrap.Modal(document.getElementById('taskFormModal'));
        const form = document.getElementById('taskForm');
        const title = document.getElementById('taskFormTitle');
        
        // Reset form
        form.reset();
        
        if (taskData) {
            title.textContent = 'Edit Task';
            document.getElementById('taskTitle').value = taskData.title || '';
            document.getElementById('taskDescription').value = taskData.description || '';
            document.getElementById('taskStatus').value = taskData.status_id || '';
            document.getElementById('taskPriority').value = taskData.priority_id || '';
            document.getElementById('taskAssignee').value = taskData.assigned_to || '';
            document.getElementById('taskBeginDate').value = taskData.begin_date || '';
            document.getElementById('taskEndDate').value = taskData.end_date || '';
        } else {
            title.textContent = 'Add New Task';
        }
        
        modal.show();
    }

    async saveTask() {
        const form = document.getElementById('taskForm');
        const formData = new FormData(form);
        
        const taskData = {
            title: document.getElementById('taskTitle').value,
            description: document.getElementById('taskDescription').value,
            status_id: parseInt(document.getElementById('taskStatus').value),
            priority_id: parseInt(document.getElementById('taskPriority').value),
            assigned_to: parseInt(document.getElementById('taskAssignee').value),
            begin_date: document.getElementById('taskBeginDate').value || null,
            end_date: document.getElementById('taskEndDate').value || null,
            _token: this.csrfToken
        };

        // Validate required fields
        if (!taskData.title || !taskData.status_id || !taskData.assigned_to) {
            this.showError('Please fill in all required fields');
            return;
        }

        this.showLoading();

        try {
            const response = await fetch(`${this.apiBaseUrl}/task/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(taskData)
            });

            const result = await response.json();

            if (result.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('taskFormModal'));
                modal.hide();
                
                // Reload board
                await this.loadBoardData();
                this.renderBoard();
                this.setupEventListeners();
                this.showSuccess('Task created successfully');
            } else {
                this.showError(result.error || 'Failed to create task');
            }
        } catch (error) {
            this.showError('Failed to create task: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async applyFilters() {
        const filters = {};
        
        const assignee = document.getElementById('filterAssignee').value;
        const priority = document.getElementById('filterPriority').value;
        
        if (assignee) filters.assigned_to = assignee;
        if (priority) filters.priority_id = priority;

        this.showLoading();
        try {
            await this.loadBoardData(filters);
            this.renderBoard();
            this.setupEventListeners();
        } catch (error) {
            this.showError('Failed to apply filters: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async clearFilters() {
        document.getElementById('filterAssignee').value = '';
        document.getElementById('filterPriority').value = '';
        
        this.showLoading();
        try {
            await this.loadBoardData();
            this.renderBoard();
            this.setupEventListeners();
        } catch (error) {
            this.showError('Failed to clear filters: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'flex';
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'danger');
    }

    showToast(message, type = 'info') {
        // Create toast if it doesn't exist
        let toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }

        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make KanbanBoard available globally
window.KanbanBoard = KanbanBoard;