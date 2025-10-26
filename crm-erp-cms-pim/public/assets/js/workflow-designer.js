/**
 * Visual Workflow Designer - Interactive Canvas for Workflow Design
 */

class WorkflowDesigner {
    constructor() {
        this.canvas = document.getElementById('workflowCanvas');
        this.stepsContainer = document.getElementById('stepsContainer');
        this.connectionsLayer = document.getElementById('connectionsLayer');
        this.steps = [];
        this.selectedStep = null;
        this.currentWorkflowId = null;
        this.stepTypes = [];
        
        this.init();
    }

    init() {
        this.loadStepTypes();
        this.setupEventListeners();
        this.setupDragAndDrop();
        this.setupContextMenu();
    }

    async loadStepTypes() {
        try {
            const response = await fetch('/api/workflow-designer/step-types');
            const data = await response.json();
            
            if (data.success) {
                this.stepTypes = data.step_types;
                this.renderStepTypePalette();
            }
        } catch (error) {
            console.error('Error loading step types:', error);
        }
    }

    renderStepTypePalette() {
        const palette = document.getElementById('stepTypePalette');
        palette.innerHTML = '';

        this.stepTypes.forEach(stepType => {
            const stepItem = document.createElement('div');
            stepItem.className = 'step-type-item';
            stepItem.draggable = true;
            stepItem.dataset.stepType = stepType.type;
            stepItem.style.borderLeftColor = stepType.color;
            
            stepItem.innerHTML = `
                <i class="fas fa-${stepType.icon}"></i>
                <div class="step-type-info">
                    <strong>${stepType.name}</strong>
                    <small class="d-block text-muted">${stepType.description}</small>
                </div>
            `;

            stepItem.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('stepType', stepType.type);
                e.dataTransfer.effectAllowed = 'copy';
            });

            palette.appendChild(stepItem);
        });
    }

    setupEventListeners() {
        document.getElementById('saveWorkflowBtn').addEventListener('click', () => this.saveWorkflow());
        document.getElementById('loadWorkflowBtn').addEventListener('click', () => this.showLoadWorkflowDialog());
        document.getElementById('validateWorkflowBtn').addEventListener('click', () => this.validateWorkflow());
        document.getElementById('clearCanvasBtn').addEventListener('click', () => {
            if (confirm('Clear canvas?')) this.clearCanvas();
        });
        document.getElementById('autoLayoutBtn').addEventListener('click', () => this.autoLayout());
    }

    setupDragAndDrop() {
        this.canvas.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        });

        this.canvas.addEventListener('drop', (e) => {
            e.preventDefault();
            const stepType = e.dataTransfer.getData('stepType');
            
            if (stepType) {
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left + this.canvas.scrollLeft;
                const y = e.clientY - rect.top + this.canvas.scrollTop;
                this.addStep(stepType, x, y);
            }
        });
    }

    addStep(stepType, x, y) {
        const stepTypeConfig = this.stepTypes.find(st => st.type === stepType);
        if (!stepTypeConfig) return;

        const stepId = 'step_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        const step = {
            id: stepId,
            step_name: stepTypeConfig.name,
            step_type: stepType,
            position: { x, y },
            required_role_id: null,
            auto_approve: false,
            notification_template: null,
            action_config: null
        };

        this.steps.push(step);
        this.renderStep(step);
        this.updateStepsList();
        this.redrawConnections();
    }

    renderStep(step) {
        const stepTypeConfig = this.stepTypes.find(st => st.type === step.step_type);
        
        const stepElement = document.createElement('div');
        stepElement.className = 'workflow-step';
        stepElement.id = step.id;
        stepElement.style.left = step.position.x + 'px';
        stepElement.style.top = step.position.y + 'px';
        stepElement.style.borderColor = stepTypeConfig.color;
        
        stepElement.innerHTML = `
            <div class="step-header" style="background-color: ${stepTypeConfig.color}">
                <i class="fas fa-${stepTypeConfig.icon}"></i>
                <span class="step-number">${this.steps.indexOf(step) + 1}</span>
            </div>
            <div class="step-body">
                <div class="step-title">${step.step_name}</div>
                <div class="step-type-label">${stepTypeConfig.name}</div>
            </div>
        `;

        stepElement.draggable = true;
        stepElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.selectStep(step.id);
        });

        stepElement.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showContextMenu(e, step.id);
        });

        stepElement.addEventListener('drag', (e) => {
            if (e.clientX === 0 && e.clientY === 0) return;
            
            const rect = this.canvas.getBoundingClientRect();
            const x = e.clientX - rect.left + this.canvas.scrollLeft - 75;
            const y = e.clientY - rect.top + this.canvas.scrollTop - 40;
            
            if (x >= 0 && y >= 0) {
                stepElement.style.left = x + 'px';
                stepElement.style.top = y + 'px';
                step.position = { x, y };
                this.redrawConnections();
            }
        });

        this.stepsContainer.appendChild(stepElement);
    }

    selectStep(stepId) {
        document.querySelectorAll('.workflow-step').forEach(el => el.classList.remove('selected'));
        
        const stepElement = document.getElementById(stepId);
        if (stepElement) stepElement.classList.add('selected');

        this.selectedStep = this.steps.find(s => s.id === stepId);
        this.showStepProperties(this.selectedStep);
    }

    showStepProperties(step) {
        const panel = document.getElementById('stepPropertiesPanel');
        const stepTypeConfig = this.stepTypes.find(st => st.type === step.step_type);
        
        let html = `
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-${stepTypeConfig.icon}"></i> ${stepTypeConfig.name}</h6>
            </div>
            <div class="card-body">
        `;

        Object.entries(stepTypeConfig.fields).forEach(([fieldName, fieldConfig]) => {
            const value = step[fieldName] || '';
            html += `<div class="mb-3"><label class="form-label">${this.formatFieldName(fieldName)}</label>`;
            
            if (fieldConfig.type === 'text') {
                html += `<input type="text" class="form-control step-property" data-field="${fieldName}" value="${value}">`;
            } else if (fieldConfig.type === 'textarea') {
                html += `<textarea class="form-control step-property" data-field="${fieldName}" rows="3">${value}</textarea>`;
            } else if (fieldConfig.type === 'select' && fieldConfig.source === 'roles') {
                html += `<select class="form-select step-property" data-field="${fieldName}">
                    <option value="">Select Role</option>`;
                window.WORKFLOW_DESIGNER_CONFIG.roles.forEach(role => {
                    const selected = role.id == value ? 'selected' : '';
                    html += `<option value="${role.id}" ${selected}>${role.name}</option>`;
                });
                html += `</select>`;
            } else if (fieldConfig.type === 'checkbox') {
                const checked = value ? 'checked' : '';
                html += `<div class="form-check">
                    <input type="checkbox" class="form-check-input step-property" data-field="${fieldName}" ${checked}>
                    <label class="form-check-label">Enable</label>
                </div>`;
            }
            
            html += `</div>`;
        });

        html += `<button class="btn btn-primary btn-sm w-100" id="updateStepBtn">
                    <i class="fas fa-save"></i> Update Step
                </button></div>`;

        panel.innerHTML = html;
        document.getElementById('updateStepBtn').addEventListener('click', () => this.updateStepProperties());
    }

    updateStepProperties() {
        if (!this.selectedStep) return;

        document.querySelectorAll('.step-property').forEach(input => {
            const field = input.dataset.field;
            this.selectedStep[field] = input.type === 'checkbox' ? input.checked : input.value;
        });

        const stepElement = document.getElementById(this.selectedStep.id);
        const titleElement = stepElement.querySelector('.step-title');
        if (titleElement && this.selectedStep.step_name) {
            titleElement.textContent = this.selectedStep.step_name;
        }

        this.updateStepsList();
        this.showSuccess('Step updated');
    }

    setupContextMenu() {
        const contextMenu = document.getElementById('contextMenu');
        document.addEventListener('click', () => contextMenu.style.display = 'none');

        contextMenu.addEventListener('click', (e) => {
            const action = e.target.closest('.context-menu-item')?.dataset.action;
            if (action && this.selectedStep) this.handleContextMenuAction(action);
            contextMenu.style.display = 'none';
        });
    }

    showContextMenu(event, stepId) {
        const contextMenu = document.getElementById('contextMenu');
        contextMenu.style.display = 'block';
        contextMenu.style.left = event.pageX + 'px';
        contextMenu.style.top = event.pageY + 'px';
        this.selectStep(stepId);
    }

    handleContextMenuAction(action) {
        const stepIndex = this.steps.findIndex(s => s.id === this.selectedStep.id);
        
        switch (action) {
            case 'duplicate':
                this.duplicateStep(this.selectedStep);
                break;
            case 'delete':
                this.deleteStep(this.selectedStep.id);
                break;
            case 'move-up':
                if (stepIndex > 0) {
                    [this.steps[stepIndex], this.steps[stepIndex - 1]] = [this.steps[stepIndex - 1], this.steps[stepIndex]];
                    this.updateStepsList();
                }
                break;
            case 'move-down':
                if (stepIndex < this.steps.length - 1) {
                    [this.steps[stepIndex], this.steps[stepIndex + 1]] = [this.steps[stepIndex + 1], this.steps[stepIndex]];
                    this.updateStepsList();
                }
                break;
        }
    }

    duplicateStep(step) {
        const newStep = JSON.parse(JSON.stringify(step));
        newStep.id = 'step_' + Date.now();
        newStep.position.x += 50;
        newStep.position.y += 50;
        newStep.step_name += ' (Copy)';
        
        this.steps.push(newStep);
        this.renderStep(newStep);
        this.updateStepsList();
    }

    deleteStep(stepId) {
        if (!confirm('Delete step?')) return;
        
        this.steps = this.steps.filter(s => s.id !== stepId);
        document.getElementById(stepId)?.remove();
        
        this.selectedStep = null;
        document.getElementById('stepPropertiesPanel').innerHTML = `
            <div class="card-body"><p class="text-muted text-center">Select a step</p></div>`;
        
        this.updateStepsList();
        this.redrawConnections();
    }

    updateStepsList() {
        const stepsList = document.getElementById('stepsList');
        
        if (this.steps.length === 0) {
            stepsList.innerHTML = '<p class="text-muted text-center m-3">No steps</p>';
            return;
        }

        stepsList.innerHTML = '';
        this.steps.forEach((step, index) => {
            const stepTypeConfig = this.stepTypes.find(st => st.type === step.step_type);
            
            const listItem = document.createElement('div');
            listItem.className = 'list-group-item list-group-item-action';
            listItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <span class="badge rounded-pill me-2" style="background-color: ${stepTypeConfig.color}">${index + 1}</span>
                    <div class="flex-grow-1">
                        <strong>${step.step_name}</strong>
                        <small class="d-block text-muted">${stepTypeConfig.name}</small>
                    </div>
                    <i class="fas fa-${stepTypeConfig.icon}" style="color: ${stepTypeConfig.color}"></i>
                </div>
            `;
            
            listItem.addEventListener('click', () => this.selectStep(step.id));
            stepsList.appendChild(listItem);
        });

        this.steps.forEach((step, index) => {
            const numberElement = document.querySelector(`#${step.id} .step-number`);
            if (numberElement) numberElement.textContent = index + 1;
        });
    }

    redrawConnections() {
        this.connectionsLayer.innerHTML = '';
        
        if (!document.getElementById('arrowhead')) {
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
            marker.setAttribute('id', 'arrowhead');
            marker.setAttribute('markerWidth', '10');
            marker.setAttribute('markerHeight', '10');
            marker.setAttribute('refX', '9');
            marker.setAttribute('refY', '3');
            marker.setAttribute('orient', 'auto');
            
            const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            polygon.setAttribute('points', '0 0, 10 3, 0 6');
            polygon.setAttribute('fill', '#6c757d');
            
            marker.appendChild(polygon);
            defs.appendChild(marker);
            this.connectionsLayer.appendChild(defs);
        }
        
        for (let i = 0; i < this.steps.length - 1; i++) {
            const fromStep = document.getElementById(this.steps[i].id);
            const toStep = document.getElementById(this.steps[i + 1].id);
            
            if (fromStep && toStep) this.drawConnection(fromStep, toStep);
        }
    }

    drawConnection(fromElement, toElement) {
        const fromRect = fromElement.getBoundingClientRect();
        const toRect = toElement.getBoundingClientRect();
        const canvasRect = this.canvas.getBoundingClientRect();
        
        const x1 = fromRect.left - canvasRect.left + fromRect.width / 2 + this.canvas.scrollLeft;
        const y1 = fromRect.bottom - canvasRect.top + this.canvas.scrollTop;
        const x2 = toRect.left - canvasRect.left + toRect.width / 2 + this.canvas.scrollLeft;
        const y2 = toRect.top - canvasRect.top + this.canvas.scrollTop;
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const controlPointOffset = Math.abs(y2 - y1) / 2;
        const d = `M ${x1} ${y1} C ${x1} ${y1 + controlPointOffset}, ${x2} ${y2 - controlPointOffset}, ${x2} ${y2}`;
        
        path.setAttribute('d', d);
        path.setAttribute('stroke', '#6c757d');
        path.setAttribute('stroke-width', '2');
        path.setAttribute('fill', 'none');
        path.setAttribute('marker-end', 'url(#arrowhead)');
        
        this.connectionsLayer.appendChild(path);
    }

    async saveWorkflow() {
        const workflowData = {
            id: this.currentWorkflowId,
            name: document.getElementById('workflowName').value,
            entity_type: document.getElementById('workflowEntity').value,
            description: document.getElementById('workflowDescription').value,
            is_active: document.getElementById('workflowActive').checked,
            steps: this.steps.map((step, index) => ({
                step_name: step.step_name,
                step_type: step.step_type,
                step_order: index + 1,
                required_role_id: step.required_role_id || null,
                auto_approve: step.auto_approve || false,
                notification_template: step.notification_template || null,
                action_config: step.action_config || null
            }))
        };

        try {
            const response = await fetch('/api/workflow-designer/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(workflowData)
            });

            const data = await response.json();
            
            if (data.success) {
                this.currentWorkflowId = data.workflow_id;
                this.showSuccess('Workflow saved!');
                document.getElementById('canvasTitle').textContent = workflowData.name;
            } else {
                this.showError(data.message || 'Save failed');
            }
        } catch (error) {
            this.showError('Error saving workflow');
        }
    }

    async showLoadWorkflowDialog() {
        try {
            const response = await fetch('/api/workflow-designer/list');
            const data = await response.json();
            
            if (data.success) {
                this.renderWorkflowsList(data.workflows);
                new bootstrap.Modal(document.getElementById('loadWorkflowModal')).show();
            }
        } catch (error) {
            this.showError('Failed to load workflows');
        }
    }

    renderWorkflowsList(workflows) {
        const tbody = document.querySelector('#workflowsTable tbody');
        tbody.innerHTML = '';

        workflows.forEach(workflow => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${workflow.name}</td>
                <td><span class="badge bg-secondary">${workflow.entity_type}</span></td>
                <td>${workflow.step_count} steps</td>
                <td>${workflow.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'}</td>
                <td>
                    <button class="btn btn-sm btn-primary load-workflow-btn" data-id="${workflow.id}">
                        <i class="fas fa-folder-open"></i> Load
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.querySelectorAll('.load-workflow-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.loadWorkflow(btn.dataset.id);
                bootstrap.Modal.getInstance(document.getElementById('loadWorkflowModal')).hide();
            });
        });
    }

    async loadWorkflow(workflowId) {
        try {
            const response = await fetch(`/api/workflow-designer/get/${workflowId}`);
            const data = await response.json();
            
            if (data.success) {
                this.clearCanvas();
                this.currentWorkflowId = data.workflow.id;
                document.getElementById('workflowName').value = data.workflow.name;
                document.getElementById('workflowEntity').value = data.workflow.entity_type;
                document.getElementById('workflowDescription').value = data.workflow.description || '';
                document.getElementById('workflowActive').checked = data.workflow.is_active;
                document.getElementById('canvasTitle').textContent = data.workflow.name;
                
                data.steps.forEach((step, index) => {
                    const stepObj = {
                        id: 'step_' + Date.now() + '_' + index,
                        step_name: step.step_name,
                        step_type: step.step_type,
                        position: { x: 100 + (index * 200), y: 100 },
                        required_role_id: step.required_role_id,
                        auto_approve: step.auto_approve,
                        notification_template: step.notification_template,
                        action_config: step.action_config ? JSON.parse(step.action_config) : null
                    };
                    
                    this.steps.push(stepObj);
                    this.renderStep(stepObj);
                });
                
                this.updateStepsList();
                this.redrawConnections();
                this.showSuccess('Workflow loaded!');
            }
        } catch (error) {
            this.showError('Failed to load workflow');
        }
    }

    async validateWorkflow() {
        const workflowData = {
            name: document.getElementById('workflowName').value,
            entity_type: document.getElementById('workflowEntity').value,
            steps: this.steps.map((step, index) => ({
                step_name: step.step_name,
                step_type: step.step_type,
                required_role_id: step.required_role_id
            }))
        };

        try {
            const response = await fetch('/api/workflow-designer/validate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(workflowData)
            });

            const data = await response.json();
            this.showValidationResults(data);
        } catch (error) {
            this.showError('Validation failed');
        }
    }

    showValidationResults(data) {
        const panel = document.getElementById('validationPanel');
        const resultsDiv = document.getElementById('validationResults');
        panel.style.display = 'block';
        
        if (data.valid) {
            panel.querySelector('.card-header').className = 'card-header bg-success text-white';
            panel.querySelector('h6').innerHTML = '<i class="fas fa-check-circle"></i> Valid';
            resultsDiv.innerHTML = '<p class="text-success mb-0">Workflow is valid!</p>';
        } else {
            panel.querySelector('.card-header').className = 'card-header bg-danger text-white';
            panel.querySelector('h6').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Invalid';
            
            let html = '<ul class="mb-0">';
            data.errors.forEach(error => html += `<li class="text-danger">${error}</li>`);
            html += '</ul>';
            resultsDiv.innerHTML = html;
        }
    }

    clearCanvas() {
        this.steps = [];
        this.stepsContainer.innerHTML = '';
        this.connectionsLayer.innerHTML = '';
        this.selectedStep = null;
        this.currentWorkflowId = null;
        document.getElementById('workflowName').value = '';
        document.getElementById('workflowEntity').value = '';
        document.getElementById('workflowDescription').value = '';
        document.getElementById('canvasTitle').textContent = 'New Workflow';
        this.updateStepsList();
    }

    autoLayout() {
        this.steps.forEach((step, index) => {
            step.position = { x: 100 + (index * 200), y: 100 };
            const stepElement = document.getElementById(step.id);
            if (stepElement) {
                stepElement.style.left = step.position.x + 'px';
                stepElement.style.top = step.position.y + 'px';
            }
        });
        this.redrawConnections();
    }

    formatFieldName(fieldName) {
        return fieldName.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    showSuccess(message) {
        alert(message);
    }

    showError(message) {
        alert('Error: ' + message);
    }
}

// Initialize designer when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.workflowDesigner = new WorkflowDesigner();
});
