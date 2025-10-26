<?php $this->layout('layouts/app', ['title' => 'Price List Details']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($priceList['name']) ?></h1>
        <div>
            <a href="/pim/price-lists/<?= $priceList['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <button class="btn btn-success" onclick="exportPriceList()">
                <i class="bi bi-download"></i> Export
            </button>
            <a href="/pim/price-lists" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Price List Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Name</th>
                            <td><strong><?= htmlspecialchars($priceList['name']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Currency</th>
                            <td><?= htmlspecialchars($priceList['currency']) ?></td>
                        </tr>
                        <tr>
                            <th>Customer Group</th>
                            <td><?= htmlspecialchars($priceList['customer_group'] ?? 'All') ?></td>
                        </tr>
                        <tr>
                            <th>Valid From</th>
                            <td><?= $priceList['valid_from'] ? date('M d, Y', strtotime($priceList['valid_from'])) : 'No start date' ?></td>
                        </tr>
                        <tr>
                            <th>Valid To</th>
                            <td><?= $priceList['valid_to'] ? date('M d, Y', strtotime($priceList['valid_to'])) : 'No end date' ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?= nl2br(htmlspecialchars($priceList['description'] ?? '-')) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php
                                $now = date('Y-m-d');
                                if ($priceList['is_active']) {
                                    if ($priceList['valid_from'] && $priceList['valid_from'] > $now) {
                                        echo '<span class="badge bg-warning">Scheduled</span>';
                                    } elseif ($priceList['valid_to'] && $priceList['valid_to'] < $now) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Active</span>';
                                    }
                                } else {
                                    echo '<span class="badge bg-secondary">Inactive</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Products & Prices (<?= count($items ?? []) ?>)</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="bi bi-plus"></i> Add Product
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product SKU</th>
                                    <th>Product Name</th>
                                    <th>Base Price</th>
                                    <th>List Price</th>
                                    <th>Discount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No products in this price list. Add some products to get started.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($item['product_sku']) ?></code></td>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>$<?= number_format($item['base_price'], 2) ?></td>
                                        <td><strong>$<?= number_format($item['price'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($item['price'] < $item['base_price']): ?>
                                                <?php $discount = (($item['base_price'] - $item['price']) / $item['base_price']) * 100; ?>
                                                <span class="badge bg-success">-<?= number_format($discount, 1) ?>%</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeItem(<?= $item['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Total Products:</strong> <?= count($items ?? []) ?></p>
                    <p class="mb-2"><strong>Average Price:</strong> $<?= number_format(array_sum(array_column($items ?? [], 'price')) / max(count($items ?? []), 1), 2) ?></p>
                    <p class="mb-0"><strong>Total Value:</strong> $<?= number_format(array_sum(array_column($items ?? [], 'price')), 2) ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="bulkAdjust()">
                            <i class="bi bi-calculator"></i> Bulk Price Adjustment
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleStatus(<?= $priceList['id'] ?>, <?= $priceList['is_active'] ?>)">
                            <i class="bi bi-toggle-on"></i> <?= $priceList['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Product to Price List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addItemForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product ID</label>
                        <input type="number" name="product_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this price list?`)) return;
    
    try {
        await Ajax.put(`/api/pim/price-lists/${id}`, { is_active: currentStatus ? 0 : 1 });
        UI.createAlert(`Price list ${action}d successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert(`Failed to ${action} price list`, 'danger');
    }
}

async function removeItem(itemId) {
    if (!confirm('Remove this product from the price list?')) return;
    
    try {
        await Ajax.delete(`/api/pim/price-list-items/${itemId}`);
        UI.createAlert('Product removed successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to remove product', 'danger');
    }
}

async function exportPriceList() {
    window.location.href = `/api/pim/price-lists/<?= $priceList['id'] ?>/export`;
}

function bulkAdjust() {
    const percentage = prompt('Enter adjustment percentage (e.g., 10 for +10%, -10 for -10%):');
    if (!percentage) return;
    
    // Implement bulk adjustment logic
    alert('Bulk adjustment feature coming soon!');
}

document.getElementById('addItemForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.price_list_id = <?= $priceList['id'] ?>;
    
    try {
        await Ajax.post('/api/pim/price-list-items', data);
        UI.createAlert('Product added successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to add product', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
