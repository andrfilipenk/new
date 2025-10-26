<?php $this->layout('layouts/app', ['title' => 'Pending Approvals']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <h1 class="mb-4">My Pending Approvals</h1>
    
    <div id="pending-approvals-container">
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
// Load pending approvals
async function loadPendingApprovals() {
    try {
        const response = await Ajax.get('/api/workflows/pending');
        const approvals = response.pending_approvals;
        
        if (approvals.length === 0) {
            document.getElementById('pending-approvals-container').innerHTML = 
                '<div class="alert alert-info">No pending approvals</div>';
            return;
        }
        
        let html = '<div class="row">';
        
        approvals.forEach(approval => {
            html += `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5>${approval.workflow_name}</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Step:</strong> ${approval.step_name}</p>
                            <p><strong>Entity:</strong> ${approval.entity_type} #${approval.entity_id}</p>
                            <p><strong>Submitted:</strong> ${approval.initiated_at}</p>
                            <div class="btn-group">
                                <button class="btn btn-success" onclick="approveWorkflow(${approval.workflow_instance_id})">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button class="btn btn-danger" onclick="rejectWorkflow(${approval.workflow_instance_id})">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                                <a href="/workflows/${approval.workflow_instance_id}/details" class="btn btn-info">
                                    <i class="bi bi-info-circle"></i> Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        document.getElementById('pending-approvals-container').innerHTML = html;
    } catch (error) {
        UI.createAlert('Failed to load pending approvals', 'danger');
    }
}

async function approveWorkflow(instanceId) {
    const comments = prompt('Enter approval comments (optional):');
    
    try {
        await Ajax.post(`/api/workflows/${instanceId}/approve`, { comments });
        UI.createAlert('Workflow approved successfully', 'success');
        loadPendingApprovals();
    } catch (error) {
        UI.createAlert('Failed to approve workflow', 'danger');
    }
}

async function rejectWorkflow(instanceId) {
    const reason = prompt('Enter rejection reason:');
    
    if (!reason) {
        UI.createAlert('Rejection reason is required', 'warning');
        return;
    }
    
    try {
        await Ajax.post(`/api/workflows/${instanceId}/reject`, { reason });
        UI.createAlert('Workflow rejected', 'success');
        loadPendingApprovals();
    } catch (error) {
        UI.createAlert('Failed to reject workflow', 'danger');
    }
}

// Load on page load
loadPendingApprovals();
</script>
<?php $this->endSection() ?>
