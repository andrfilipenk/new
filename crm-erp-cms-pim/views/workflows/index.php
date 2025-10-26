<?php $this->layout('layouts/app', ['title' => 'Workflows']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Workflows</h1>
        <a href="/workflows/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Workflow
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Workflows</h6>
                    <h3><?= count($workflows ?? []) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active</h6>
                    <h3><?= count(array_filter($workflows ?? [], fn($w) => $w['is_active'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Running Instances</h6>
                    <h3><?= $stats['running_instances'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending Approvals</h6>
                    <h3><?= $stats['pending_approvals'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Entity Type</label>
                    <select name="entity_type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="PurchaseOrder" <?= ($entityType ?? '') === 'PurchaseOrder' ? 'selected' : '' ?>>Purchase Order</option>
                        <option value="SalesOrder" <?= ($entityType ?? '') === 'SalesOrder' ? 'selected' : '' ?>>Sales Order</option>
                        <option value="Invoice" <?= ($entityType ?? '') === 'Invoice' ? 'selected' : '' ?>>Invoice</option>
                        <option value="Opportunity" <?= ($entityType ?? '') === 'Opportunity' ? 'selected' : '' ?>>Opportunity</option>
                        <option value="Product" <?= ($entityType ?? '') === 'Product' ? 'selected' : '' ?>>Product</option>
                        <option value="Page" <?= ($entityType ?? '') === 'Page' ? 'selected' : '' ?>>Page</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Workflows Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Workflow Name</th>
                            <th>Entity Type</th>
                            <th>Steps</th>
                            <th>Instances</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($workflows)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No workflows found. <a href="/workflows/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($workflows as $workflow): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($workflow['name']) ?></strong>
                                    <?php if ($workflow['description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($workflow['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($workflow['entity_type']) ?></span></td>
                                <td><?= $workflow['step_count'] ?? 0 ?> steps</td>
                                <td>
                                    <?= $workflow['instance_count'] ?? 0 ?> total
                                    <?php if (($workflow['running_count'] ?? 0) > 0): ?>
                                    <br><small class="text-info"><?= $workflow['running_count'] ?> running</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $workflow['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $workflow['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($workflow['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/workflows/<?= $workflow['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <a href="/workflows/<?= $workflow['id'] ?>/designer" class="btn btn-outline-secondary">Designer</a>
                                        <button class="btn btn-outline-info" onclick="toggleWorkflow(<?= $workflow['id'] ?>, <?= $workflow['is_active'] ?>)">
                                            <?= $workflow['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function toggleWorkflow(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this workflow?`)) return;
    
    try {
        await Ajax.put(`/api/workflows/${id}`, { is_active: currentStatus ? 0 : 1 });
        UI.createAlert(`Workflow ${action}d successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert(`Failed to ${action} workflow`, 'danger');
    }
}
</script>
<?php $this->endSection() ?>
