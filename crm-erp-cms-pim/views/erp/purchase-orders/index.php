<?php $this->layout('layouts/app', ['title' => 'Purchase Orders']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Purchase Orders</h1>
        <a href="/erp/purchases/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Purchase Order
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Draft</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'draft')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Pending</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'pending')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Sent</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'sent')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Partial</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'partial')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Completed</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'completed')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Cancelled</h6>
                    <h3><?= count(array_filter($purchaseOrders, fn($po) => $po['status'] === 'cancelled')) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by PO number..." value="<?= $_GET['search'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="sent" <?= ($status ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="partial" <?= ($status ?? '') === 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="completed" <?= ($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="/erp/purchases" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Purchase Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Vendor</th>
                            <th>Order Date</th>
                            <th>Expected Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchaseOrders)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No purchase orders found. <a href="/erp/purchases/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($purchaseOrders as $po): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($po['po_number']) ?></strong></td>
                                <td>Vendor #<?= $po['vendor_id'] ?></td>
                                <td><?= date('M d, Y', strtotime($po['order_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($po['expected_date'])) ?></td>
                                <td><strong>$<?= number_format($po['total_amount'], 2) ?></strong></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending' => 'warning',
                                        'sent' => 'info',
                                        'partial' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'rejected' => 'danger'
                                    ];
                                    $color = $statusColors[$po['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>">
                                        <?= ucfirst($po['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/erp/purchases/<?= $po['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <?php if ($po['status'] === 'draft'): ?>
                                        <button class="btn btn-outline-success" onclick="approvePO(<?= $po['id'] ?>)">Approve</button>
                                        <?php endif; ?>
                                        <?php if ($po['status'] === 'sent' || $po['status'] === 'partial'): ?>
                                        <button class="btn btn-outline-info" onclick="receivePO(<?= $po['id'] ?>)">Receive</button>
                                        <?php endif; ?>
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
async function approvePO(id) {
    if (!confirm('Approve this purchase order?')) return;
    
    try {
        await Ajax.post(`/api/erp/purchase-orders/${id}/approve`, {});
        UI.createAlert('Purchase order approved successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to approve purchase order', 'danger');
    }
}

async function receivePO(id) {
    window.location.href = `/erp/purchases/${id}?action=receive`;
}

async function cancelPO(id) {
    const reason = prompt('Enter cancellation reason:');
    if (!reason) return;
    
    try {
        await Ajax.post(`/api/erp/purchase-orders/${id}/cancel`, { reason });
        UI.createAlert('Purchase order cancelled', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to cancel purchase order', 'danger');
    }
}
</script>
<?php $this->endSection() ?>
