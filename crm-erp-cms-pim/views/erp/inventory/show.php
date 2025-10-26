<?php $this->layout('layouts/app', ['title' => 'Inventory Details']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Inventory Details</h1>
        <div>
            <button class="btn btn-primary" onclick="openAdjustModal()">
                <i class="bi bi-arrow-down-up"></i> Adjust Stock
            </button>
            <a href="/erp/inventory" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <!-- Inventory Info -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Product ID</th>
                            <td>Product #<?= $inventory['product_id'] ?></td>
                        </tr>
                        <tr>
                            <th>Warehouse ID</th>
                            <td>Warehouse #<?= $inventory['warehouse_id'] ?></td>
                        </tr>
                        <tr>
                            <th>Total Quantity</th>
                            <td><strong><?= number_format($inventory['quantity']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Reserved Quantity</th>
                            <td><?= number_format($inventory['reserved_quantity']) ?></td>
                        </tr>
                        <tr>
                            <th>Available Quantity</th>
                            <td>
                                <?php if ($inventory['available_quantity'] < 10): ?>
                                <span class="text-danger"><strong><?= number_format($inventory['available_quantity']) ?></strong></span>
                                <?php else: ?>
                                <strong><?= number_format($inventory['available_quantity']) ?></strong>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Average Cost</th>
                            <td>$<?= number_format($inventory['average_cost'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>Total Value</th>
                            <td><strong>$<?= number_format($inventory['quantity'] * $inventory['average_cost'], 2) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?= $inventory['updated_at'] ? date('M d, Y H:i', strtotime($inventory['updated_at'])) : '-' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <?php if ($inventory['available_quantity'] <= 0): ?>
                            <div class="alert alert-danger">
                                <strong>Out of Stock</strong> - No items available
                            </div>
                        <?php elseif ($inventory['available_quantity'] < 10): ?>
                            <div class="alert alert-warning">
                                <strong>Low Stock Alert</strong> - Only <?= $inventory['available_quantity'] ?> items available
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>In Stock</strong> - <?= $inventory['available_quantity'] ?> items available
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="progress mb-3" style="height: 30px;">
                        <?php 
                        $reservedPercent = $inventory['quantity'] > 0 ? ($inventory['reserved_quantity'] / $inventory['quantity']) * 100 : 0;
                        $availablePercent = $inventory['quantity'] > 0 ? ($inventory['available_quantity'] / $inventory['quantity']) * 100 : 0;
                        ?>
                        <div class="progress-bar bg-warning" style="width: <?= $reservedPercent ?>%">
                            Reserved (<?= number_format($reservedPercent, 1) ?>%)
                        </div>
                        <div class="progress-bar bg-success" style="width: <?= $availablePercent ?>%">
                            Available (<?= number_format($availablePercent, 1) ?>%)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Stock Movements -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Stock Movements</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No stock movements recorded
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?= date('M d, Y H:i', strtotime($movement['movement_date'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $movement['type'] === 'in' ? 'success' : ($movement['type'] === 'out' ? 'danger' : 'info') ?>">
                                        <?= ucfirst($movement['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($movement['quantity'] > 0): ?>
                                    <span class="text-success">+<?= number_format($movement['quantity']) ?></span>
                                    <?php else: ?>
                                    <span class="text-danger"><?= number_format($movement['quantity']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($movement['reference'] ?? '-') ?></td>
                                <td>User #<?= $movement['created_by'] ?? '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm">
                <input type="hidden" name="product_id" value="<?= $inventory['product_id'] ?>">
                <input type="hidden" name="warehouse_id" value="<?= $inventory['warehouse_id'] ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        Current Quantity: <strong><?= $inventory['quantity'] ?></strong>
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
                        <input type="number" name="cost" class="form-control" step="0.01" min="0" value="<?= $inventory['average_cost'] ?>">
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
function openAdjustModal() {
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
