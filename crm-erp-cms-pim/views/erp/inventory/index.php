<?php $this->layout('layouts/app', ['title' => 'Inventory Management']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Inventory Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
            <i class="bi bi-arrow-down-up"></i> Adjust Stock
        </button>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Items</h6>
                    <h3><?= number_format($stats['total_items']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Quantity</h6>
                    <h3><?= number_format($stats['total_quantity']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Value</h6>
                    <h3>$<?= number_format($stats['total_value'], 2) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Low Stock Items</h6>
                    <h3><?= number_format($stats['low_stock_items']) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= $warehouse['id'] ?>" <?= ($warehouseId ?? '') == $warehouse['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($warehouse['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Inventory Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Warehouse</th>
                            <th>Total Quantity</th>
                            <th>Reserved</th>
                            <th>Available</th>
                            <th>Avg Cost</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No inventory found.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><strong>Product #<?= $item['product_id'] ?></strong></td>
                                <td>Warehouse #<?= $item['warehouse_id'] ?></td>
                                <td><?= number_format($item['quantity']) ?></td>
                                <td><?= number_format($item['reserved_quantity']) ?></td>
                                <td>
                                    <?php if ($item['available_quantity'] < 10): ?>
                                    <span class="text-danger"><strong><?= number_format($item['available_quantity']) ?></strong></span>
                                    <?php else: ?>
                                    <?= number_format($item['available_quantity']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($item['average_cost'], 2) ?></td>
                                <td>$<?= number_format($item['quantity'] * $item['average_cost'], 2) ?></td>
                                <td>
                                    <?php if ($item['available_quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($item['available_quantity'] < 10): ?>
                                        <span class="badge bg-warning">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/erp/inventory/<?= $item['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <button class="btn btn-outline-secondary" onclick="openAdjustModal(<?= $item['id'] ?>, <?= $item['product_id'] ?>, <?= $item['warehouse_id'] ?>, <?= $item['quantity'] ?>)">Adjust</button>
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product ID</label>
                        <input type="number" name="product_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Warehouse ID</label>
                        <select name="warehouse_id" class="form-select" required>
                            <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?= $warehouse['id'] ?>"><?= htmlspecialchars($warehouse['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="type" class="form-select" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Quantity</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-control" required min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost per Unit (for additions)</label>
                        <input type="number" name="cost" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAdjustModal(id, productId, warehouseId, currentQuantity) {
    const form = document.getElementById('adjustForm');
    form.product_id.value = productId;
    form.warehouse_id.value = warehouseId;
    form.quantity.value = '';
    form.reason.value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('adjustModal'));
    modal.show();
}

document.getElementById('adjustForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        await Ajax.post('/api/erp/inventory/adjust', data);
        UI.createAlert('Stock adjusted successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to adjust stock', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
