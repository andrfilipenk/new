<?php $this->layout('layouts/app', ['title' => 'Workflow Details']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Workflow Details</h1>
        <a href="/workflows/pending" class="btn btn-secondary">Back to Pending</a>
    </div>
    
    <div id="workflow-details-container">
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
const instanceId = <?= $instanceId ?? 'null' ?>;

async function loadWorkflowDetails() {
    if (!instanceId) {
        UI.createAlert('Invalid workflow instance', 'danger');
        return;
    }
    
    try {
        // Get details
        const details = await Ajax.get(`/api/workflows/${instanceId}/details`);
        
        // Get HTML report
        const reportResponse = await Ajax.get(`/api/workflows/${instanceId}/report`);
        const html = reportResponse.html;
        
        document.getElementById('workflow-details-container').innerHTML = html;
        
        // Add action buttons if workflow is in progress
        if (details.instance.status === 'in_progress') {
            const actionHtml = `
                <div class="mt-4">
                    <button class="btn btn-success" onclick="approveWorkflow()">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <button class="btn btn-danger" onclick="rejectWorkflow()">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    <button class="btn btn-warning" onclick="cancelWorkflow()">
                        <i class="bi bi-ban"></i> Cancel
                    </button>
                </div>
            `;
            document.getElementById('workflow-details-container').innerHTML += actionHtml;
        }
    } catch (error) {
        UI.createAlert('Failed to load workflow details', 'danger');
    }
}

async function approveWorkflow() {
    const comments = prompt('Enter approval comments (optional):');
    
    try {
        await Ajax.post(`/api/workflows/${instanceId}/approve`, { comments });
        UI.createAlert('Workflow approved successfully', 'success');
        setTimeout(() => loadWorkflowDetails(), 1000);
    } catch (error) {
        UI.createAlert('Failed to approve workflow', 'danger');
    }
}

async function rejectWorkflow() {
    const reason = prompt('Enter rejection reason:');
    
    if (!reason) {
        UI.createAlert('Rejection reason is required', 'warning');
        return;
    }
    
    try {
        await Ajax.post(`/api/workflows/${instanceId}/reject`, { reason });
        UI.createAlert('Workflow rejected', 'success');
        setTimeout(() => loadWorkflowDetails(), 1000);
    } catch (error) {
        UI.createAlert('Failed to reject workflow', 'danger');
    }
}

async function cancelWorkflow() {
    const reason = prompt('Enter cancellation reason:');
    
    if (!reason) return;
    
    try {
        await Ajax.post(`/api/workflows/${instanceId}/cancel`, { reason });
        UI.createAlert('Workflow cancelled', 'success');
        setTimeout(() => loadWorkflowDetails(), 1000);
    } catch (error) {
        UI.createAlert('Failed to cancel workflow', 'danger');
    }
}

// Load on page load
loadWorkflowDetails();
</script>
<?php $this->endSection() ?>
