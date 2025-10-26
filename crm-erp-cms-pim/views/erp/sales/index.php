<?php $this->layout('layouts/app', ['title' => 'Sales Orders']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sales Orders</h1>
        <a href="/erp/sales/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Order
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2><?= count($orders) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2><?= count(array_filter($orders, fn($o) => $o['status'] === 'pending')) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Confirmed</h5>
                    <h2><?= count(array_filter($orders, fn($o) => $o['status'] === 'confirmed')) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Fulfilled</h5>
                    <h2><?= count(array_filter($orders, fn($o) => $o['status'] === 'fulfilled')) ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                            <td>Customer #<?= $order['customer_id'] ?></td>
                            <td><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $order['status'] === 'fulfilled' ? 'success' : ($order['status'] === 'confirmed' ? 'info' : 'warning') ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($order['status'] === 'pending'): ?>
                                    <button class="btn btn-success" onclick="confirmOrder(<?= $order['id'] ?>)">Confirm</button>
                                    <?php elseif ($order['status'] === 'confirmed'): ?>
                                    <button class="btn btn-primary" onclick="fulfillOrder(<?= $order['id'] ?>)">Fulfill</button>
                                    <?php endif; ?>
                                    <a href="/erp/sales/<?= $order['id'] ?>" class="btn btn-outline-primary">View</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function confirmOrder(id) {
    try {
        await Ajax.post(`/api/erp/sales/orders/${id}/confirm`);
        UI.createAlert('Order confirmed', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to confirm order', 'danger');
    }
}

async function fulfillOrder(id) {
    const warehouseId = prompt('Enter Warehouse ID:');
    if (!warehouseId) return;
    
    try {
        await Ajax.post(`/api/erp/sales/orders/${id}/fulfill`, { warehouse_id: parseInt(warehouseId) });
        UI.createAlert('Order fulfilled', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to fulfill order', 'danger');
    }
}
</script>
<?php $this->endSection() ?>
