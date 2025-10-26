<?php $this->layout('layouts/app', ['title' => 'Pending Approvals']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Pending Approvals</h1>
        <a href="/workflows" class="btn btn-secondary">
            <i class="bi bi-list"></i> All Workflows
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending Approvals</h6>
                    <h3><?= count($pendingApprovals ?? []) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Today</h6>
                    <h3><?= count(array_filter($pendingApprovals ?? [], fn($a) => date('Y-m-d', strtotime($a['initiated_at'])) === date('Y-m-d'))) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Overdue</h6>
                    <h3><?= count(array_filter($pendingApprovals ?? [], fn($a) => strtotime($a['initiated_at']) < strtotime('-3 days'))) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Completed Today</h6>
                    <h3><?= $stats['completed_today'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Approvals -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Your Pending Approvals</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pendingApprovals)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-check-circle" style="font-size: 48px;"></i>
                <h4 class="mt-3">All Caught Up!</h4>
                <p>You have no pending approvals at this time.</p>
            </div>
            <?php else: ?>
                <?php foreach ($pendingApprovals as $approval): ?>
                <div class="approval-item card mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-2">
                                    <?= htmlspecialchars($approval['workflow_name']) ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($approval['step_name']) ?></span>
                                </h5>
                                
                                <div class="mb-2">
                                    <strong>Entity:</strong> 
                                    <span class="badge bg-info"><?= htmlspecialchars($approval['entity_type']) ?></span>
                                    <code>#<?= $approval['entity_id'] ?></code>
                                </div>
                                
                                <div class="mb-2">
                                    <i class="bi bi-person"></i>
                                    <strong>Initiated by:</strong> User #<?= $approval['initiated_by'] ?>
                                    <span class="text-muted ms-2">
                                        <i class="bi bi-clock"></i>
                                        <?php
                                        $initiatedDate = strtotime($approval['initiated_at']);
                                        $daysAgo = floor((time() - $initiatedDate) / 86400);
                                        if ($daysAgo === 0) {
                                            echo 'Today at ' . date('H:i', $initiatedDate);
                                        } elseif ($daysAgo === 1) {
                                            echo 'Yesterday at ' . date('H:i', $initiatedDate);
                                        } else {
                                            echo $daysAgo . ' days ago';
                                        }
                                        ?>
                                    </span>
                                    <?php if ($daysAgo >= 3): ?>
                                    <span class="badge bg-danger ms-2">Overdue</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($approval['comments'])): ?>
                                <div class="alert alert-light mt-2 mb-0">
                                    <small><strong>Previous Comments:</strong> <?= nl2br(htmlspecialchars($approval['comments'])) ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4 text-end">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" onclick="showApprovalModal(<?= $approval['workflow_instance_id'] ?>, 'approve', '<?= htmlspecialchars($approval['workflow_name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                    <button class="btn btn-danger" onclick="showApprovalModal(<?= $approval['workflow_instance_id'] ?>, 'reject', '<?= htmlspecialchars($approval['workflow_name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                    <a href="/workflows/instances/<?= $approval['workflow_instance_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm">
                <input type="hidden" id="instanceId" name="instance_id">
                <input type="hidden" id="approvalAction" name="action">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Comments</label>
                        <textarea name="comments" id="approvalComments" class="form-control" rows="4" required></textarea>
                        <small class="text-muted">Provide a reason for your decision</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="approvalSubmitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentModal;

function showApprovalModal(instanceId, action, workflowName) {
    document.getElementById('instanceId').value = instanceId;
    document.getElementById('approvalAction').value = action;
    document.getElementById('approvalComments').value = '';
    
    const title = action === 'approve' ? 'Approve Request' : 'Reject Request';
    document.getElementById('approvalModalTitle').textContent = title + ': ' + workflowName;
    
    const submitBtn = document.getElementById('approvalSubmitBtn');
    submitBtn.className = action === 'approve' ? 'btn btn-success' : 'btn btn-danger';
    submitBtn.innerHTML = action === 'approve' 
        ? '<i class="bi bi-check-circle"></i> Approve' 
        : '<i class="bi bi-x-circle"></i> Reject';
    
    currentModal = new bootstrap.Modal(document.getElementById('approvalModal'));
    currentModal.show();
}

document.getElementById('approvalForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const instanceId = document.getElementById('instanceId').value;
    const action = document.getElementById('approvalAction').value;
    const comments = document.getElementById('approvalComments').value;
    
    try {
        const endpoint = action === 'approve' ? 'approve' : 'reject';
        await Ajax.post(`/api/workflows/${instanceId}/${endpoint}`, { 
            comments: comments
        });
        
        UI.createAlert(`Request ${action}d successfully`, 'success');
        currentModal.hide();
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert(`Failed to ${action} request`, 'danger');
    }
});
</script>

<style>
.approval-item {
    border-left: 4px solid #ffc107;
    transition: all 0.3s ease;
}

.approval-item:hover {
    border-left-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}
</style>
<?php $this->endSection() ?>
