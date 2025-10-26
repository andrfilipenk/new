<?php $this->layout('layouts/app', ['title' => 'Purchase Order ' . $po['po_number']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Purchase Order: <?= htmlspecialchars($po['po_number']) ?></h1>
        <div>
            <?php if ($po['status'] === 'draft'): ?>
            <button class="btn btn-success" onclick="approvePO()">
                <i class="bi bi-check-circle"></i> Approve
            </button>
            <?php endif; ?>
            <a href="/erp/purchases" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- PO Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Purchase Order Details</h5>
                    <span class="badge bg-<?= $po['status'] === 'completed' ? 'success' : ($po['status'] === 'sent' ? 'info' : 'warning') ?> fs-6">
                        <?= ucfirst($po['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Vendor:</strong> 
                            <?= $vendor ? htmlspecialchars($vendor['name']) : 'Vendor #' . $po['vendor_id'] ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Warehouse:</strong> 
                            <?= $warehouse ? htmlspecialchars($warehouse['name']) : 'Warehouse #' . $po['warehouse_id'] ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Order Date:</strong> 
                            <?= date('M d, Y', strtotime($po['order_date'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Expected Delivery:</strong> 
                            <?= date('M d, Y', strtotime($po['expected_date'])) ?>
                        </div>
                    </div>
                    <?php if ($po['received_date']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Received Date:</strong> 
                            <?= date('M d, Y', strtotime($po['received_date'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($po['payment_terms']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Payment Terms:</strong> 
                            <?= htmlspecialchars($po['payment_terms']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($po['notes']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($po['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Line Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product/Description</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Line Total</th>
                                    <?php if ($po['status'] === 'sent' || $po['status'] === 'partial'): ?>
                                    <th>Received</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No items found</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['description']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?></td>
                                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                        <td>
                                            <?= $item['tax_rate'] ?>%
                                            <br><small class="text-muted">$<?= number_format($item['tax_amount'], 2) ?></small>
                                        </td>
                                        <td><strong>$<?= number_format($item['line_total'], 2) ?></strong></td>
                                        <?php if ($po['status'] === 'sent' || $po['status'] === 'partial'): ?>
                                        <td>
                                            <span class="badge bg-<?= $item['received_quantity'] >= $item['quantity'] ? 'success' : 'warning' ?>">
                                                <?= number_format($item['received_quantity'], 2) ?> / <?= number_format($item['quantity'], 2) ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="<?= ($po['status'] === 'sent' || $po['status'] === 'partial') ? '5' : '4' ?>" class="text-end">
                                        <strong>Subtotal:</strong>
                                    </td>
                                    <td><strong>$<?= number_format($po['subtotal'], 2) ?></strong></td>
                                </tr>
                                <?php if ($po['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="<?= ($po['status'] === 'sent' || $po['status'] === 'partial') ? '5' : '4' ?>" class="text-end">
                                        Discount:
                                    </td>
                                    <td>-$<?= number_format($po['discount_amount'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($po['shipping_cost'] > 0): ?>
                                <tr>
                                    <td colspan="<?= ($po['status'] === 'sent' || $po['status'] === 'partial') ? '5' : '4' ?>" class="text-end">
                                        Shipping:
                                    </td>
                                    <td>$<?= number_format($po['shipping_cost'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="<?= ($po['status'] === 'sent' || $po['status'] === 'partial') ? '5' : '4' ?>" class="text-end">
                                        <strong>Tax:</strong>
                                    </td>
                                    <td><strong>$<?= number_format($po['tax_amount'], 2) ?></strong></td>
                                </tr>
                                <tr class="table-active">
                                    <td colspan="<?= ($po['status'] === 'sent' || $po['status'] === 'partial') ? '5' : '4' ?>" class="text-end">
                                        <h5>Total:</h5>
                                    </td>
                                    <td><h5>$<?= number_format($po['total_amount'], 2) ?></h5></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Actions -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($po['status'] === 'draft'): ?>
                        <button class="btn btn-success" onclick="approvePO()">
                            <i class="bi bi-check-circle"></i> Approve & Send
                        </button>
                        <button class="btn btn-outline-danger" onclick="rejectPO()">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                        <?php elseif ($po['status'] === 'sent' || $po['status'] === 'partial'): ?>
                        <button class="btn btn-primary" onclick="showReceiveModal()">
                            <i class="bi bi-box-seam"></i> Receive Items
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($po['status'] !== 'completed' && $po['status'] !== 'cancelled'): ?>
                        <button class="btn btn-outline-warning" onclick="cancelPO()">
                            <i class="bi bi-ban"></i> Cancel Order
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-secondary" onclick="printPO()">
                            <i class="bi bi-printer"></i> Print PO
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick Info -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Quick Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Created:</strong><br>
                        <?= date('M d, Y H:i', strtotime($po['created_at'])) ?>
                    </div>
                    <?php if ($po['updated_at']): ?>
                    <div class="mb-2">
                        <strong>Last Updated:</strong><br>
                        <?= date('M d, Y H:i', strtotime($po['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($po['approved_by']): ?>
                    <div>
                        <strong>Approved By:</strong><br>
                        User #<?= $po['approved_by'] ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receive Items Modal (hidden by default) -->
<div id="receiveModal" style="display:none;">
    <!-- Modal would be implemented here for receiving items -->
</div>

<script>
async function approvePO() {
    if (!confirm('Approve and send this purchase order to vendor?')) return;
    
    try {
        await Ajax.post(`/api/erp/purchase-orders/<?= $po['id'] ?>/approve`, {});
        UI.createAlert('Purchase order approved and sent', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to approve purchase order', 'danger');
    }
}

async function rejectPO() {
    const reason = prompt('Enter rejection reason:');
    if (!reason) return;
    
    try {
        await Ajax.post(`/api/erp/purchase-orders/<?= $po['id'] ?>/reject`, { reason });
        UI.createAlert('Purchase order rejected', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to reject purchase order', 'danger');
    }
}

async function cancelPO() {
    const reason = prompt('Enter cancellation reason:');
    if (!reason) return;
    
    try {
        await Ajax.put(`/api/erp/purchase-orders/<?= $po['id'] ?>`, { 
            status: 'cancelled',
            notes: (<?= json_encode($po['notes'] ?? '') ?>) + '\nCancelled: ' + reason
        });
        UI.createAlert('Purchase order cancelled', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to cancel purchase order', 'danger');
    }
}

function showReceiveModal() {
    // Simple implementation - could be enhanced with a modal
    const items = <?= json_encode($items) ?>;
    let receivedItems = [];
    
    items.forEach(item => {
        const remaining = item.quantity - item.received_quantity;
        if (remaining > 0) {
            const qty = prompt(`Receive quantity for: ${item.description}\nRemaining: ${remaining}`);
            if (qty && parseFloat(qty) > 0) {
                receivedItems.push({
                    id: item.id,
                    quantity: parseFloat(qty)
                });
            }
        }
    });
    
    if (receivedItems.length > 0) {
        receiveItems(receivedItems);
    }
}

async function receiveItems(items) {
    try {
        await Ajax.post(`/api/erp/purchase-orders/<?= $po['id'] ?>/receive`, { 
            items: JSON.stringify(items) 
        });
        UI.createAlert('Items received successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to receive items', 'danger');
    }
}

function printPO() {
    window.print();
}
</script>
<?php $this->endSection() ?>
