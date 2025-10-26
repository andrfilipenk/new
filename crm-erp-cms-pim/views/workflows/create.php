<?php $this->layout('layouts/app', ['title' => 'Create Workflow']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Workflow</h1>
        <a href="/workflows" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Workflow Information</h5>
                </div>
                <div class="card-body">
                    <form id="workflowForm">
                        <div class="mb-3">
                            <label class="form-label">Workflow Name *</label>
                            <input type="text" name="name" class="form-control" required>
                            <small class="text-muted">E.g., "Purchase Order Approval", "Content Publishing Workflow"</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Entity Type *</label>
                            <select name="entity_type" id="entityType" class="form-select" required>
                                <option value="">Select Entity Type</option>
                                <option value="PurchaseOrder">Purchase Order</option>
                                <option value="SalesOrder">Sales Order</option>
                                <option value="Invoice">Invoice</option>
                                <option value="Opportunity">Opportunity</option>
                                <option value="Product">Product</option>
                                <option value="Page">Page (CMS)</option>
                                <option value="Post">Post (CMS)</option>
                            </select>
                            <small class="text-muted">Type of entity this workflow applies to</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">Active Workflow</label>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Workflow Steps</h5>
                    <button type="button" class="btn btn-sm btn-primary" onclick="addStep()">
                        <i class="bi bi-plus"></i> Add Step
                    </button>
                </div>
                <div class="card-body">
                    <div id="stepsList">
                        <!-- Steps will be added here -->
                    </div>
                    <div id="emptySteps" class="text-center text-muted py-4">
                        No steps added yet. Click "Add Step" to create your workflow.
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="/workflows" class="btn btn-secondary">Cancel</a>
                <button type="button" class="btn btn-primary" onclick="saveWorkflow()">Create Workflow</button>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Workflow Types</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2"><strong>Approval:</strong> Requires user approval to proceed</p>
                    <p class="text-muted small mb-2"><strong>Notification:</strong> Sends notification without blocking</p>
                    <p class="text-muted small mb-0"><strong>Automation:</strong> Automatic action execution</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Available Roles</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($roles)): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($roles as $role): ?>
                        <li class="mb-1">
                            <span class="badge bg-secondary"><?= htmlspecialchars($role['name']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted small mb-0">No roles found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step Template -->
<template id="stepTemplate">
    <div class="step-item card mb-3" data-step-order="">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Step <span class="step-number"></span></strong>
                <span class="badge bg-secondary step-type-badge"></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeStep(this)">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Step Name *</label>
                    <input type="text" class="form-control step-name" placeholder="E.g., Manager Approval" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Step Type *</label>
                    <select class="form-select step-type" required>
                        <option value="approval">Approval</option>
                        <option value="notification">Notification</option>
                        <option value="automation">Automation</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Required Role</label>
                    <select class="form-select step-role">
                        <option value="">None</option>
                        <?php if (!empty($roles)): foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Required Permission</label>
                    <input type="text" class="form-control step-permission" placeholder="E.g., approve_purchase_order">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input step-can-reject" checked>
                        <label class="form-check-label">Can Reject</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Auto-approve After (hours)</label>
                    <input type="number" class="form-control step-auto-approve" min="0" placeholder="Optional">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let stepCounter = 0;

function addStep() {
    stepCounter++;
    const template = document.getElementById('stepTemplate');
    const clone = template.content.cloneNode(true);
    
    const stepItem = clone.querySelector('.step-item');
    stepItem.dataset.stepOrder = stepCounter;
    clone.querySelector('.step-number').textContent = stepCounter;
    clone.querySelector('.step-type-badge').textContent = 'Approval';
    
    // Update type badge on change
    clone.querySelector('.step-type').addEventListener('change', (e) => {
        const badge = e.target.closest('.step-item').querySelector('.step-type-badge');
        badge.textContent = e.target.options[e.target.selectedIndex].text;
    });
    
    document.getElementById('stepsList').appendChild(clone);
    document.getElementById('emptySteps').style.display = 'none';
    
    updateStepNumbers();
}

function removeStep(button) {
    button.closest('.step-item').remove();
    updateStepNumbers();
    
    if (document.querySelectorAll('.step-item').length === 0) {
        document.getElementById('emptySteps').style.display = 'block';
    }
}

function updateStepNumbers() {
    const steps = document.querySelectorAll('.step-item');
    steps.forEach((step, index) => {
        step.dataset.stepOrder = index + 1;
        step.querySelector('.step-number').textContent = index + 1;
    });
}

async function saveWorkflow() {
    const formData = new FormData(document.getElementById('workflowForm'));
    const workflowData = Object.fromEntries(formData);
    workflowData.is_active = formData.has('is_active') ? 1 : 0;
    
    // Collect steps
    const steps = [];
    document.querySelectorAll('.step-item').forEach((stepEl, index) => {
        steps.push({
            name: stepEl.querySelector('.step-name').value,
            order: index + 1,
            type: stepEl.querySelector('.step-type').value,
            required_role_id: stepEl.querySelector('.step-role').value || null,
            required_permission: stepEl.querySelector('.step-permission').value || null,
            can_reject: stepEl.querySelector('.step-can-reject').checked ? 1 : 0,
            auto_approve_after_hours: stepEl.querySelector('.step-auto-approve').value || null
        });
    });
    
    if (steps.length === 0) {
        UI.createAlert('Please add at least one step to the workflow', 'warning');
        return;
    }
    
    workflowData.steps = steps;
    
    try {
        const response = await Ajax.post('/api/workflows', workflowData);
        UI.createAlert('Workflow created successfully', 'success');
        setTimeout(() => {
            window.location.href = '/workflows/' + response.workflow.id;
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to create workflow', 'danger');
    }
}

// Add first step on load
window.addEventListener('DOMContentLoaded', () => {
    addStep();
});
</script>
<?php $this->endSection() ?>
