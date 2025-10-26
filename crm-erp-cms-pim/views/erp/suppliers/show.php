<?php $this->layout('layouts/app', ['title' => 'Supplier Details']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($supplier['company_name']) ?></h1>
        <div>
            <a href="/erp/suppliers/<?= $supplier['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/erp/suppliers" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Supplier Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Supplier Code</th>
                            <td><strong><?= htmlspecialchars($supplier['supplier_code']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Company Name</th>
                            <td><?= htmlspecialchars($supplier['company_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><a href="mailto:<?= htmlspecialchars($supplier['email']) ?>"><?= htmlspecialchars($supplier['email']) ?></a></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= htmlspecialchars($supplier['phone']) ?></td>
                        </tr>
                        <tr>
                            <th>Website</th>
                            <td>
                                <?php if ($supplier['website']): ?>
                                <a href="<?= htmlspecialchars($supplier['website']) ?>" target="_blank"><?= htmlspecialchars($supplier['website']) ?></a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>
                                <?= htmlspecialchars($supplier['address']) ?><br>
                                <?= htmlspecialchars($supplier['city']) ?>, <?= htmlspecialchars($supplier['state'] ?? '') ?><br>
                                <?= htmlspecialchars($supplier['country']) ?> <?= htmlspecialchars($supplier['postal_code'] ?? '') ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Tax ID</th>
                            <td><?= htmlspecialchars($supplier['tax_id'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>Rating</th>
                            <td>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php if ($i < $supplier['rating']): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-muted"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Terms</th>
                            <td><?= ucfirst(str_replace('_', ' ', $supplier['payment_terms'] ?? 'net_30')) ?></td>
                        </tr>
                        <tr>
                            <th>Currency</th>
                            <td><?= htmlspecialchars($supplier['currency'] ?? 'USD') ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?= $supplier['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($supplier['notes']): ?>
                        <tr>
                            <th>Notes</th>
                            <td><?= nl2br(htmlspecialchars($supplier['notes'])) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/erp/purchase-orders/create?supplier_id=<?= $supplier['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i> Create Purchase Order
                        </a>
                        <button class="btn btn-outline-secondary" onclick="toggleStatus(<?= $supplier['id'] ?>, <?= $supplier['is_active'] ?>)">
                            <i class="bi bi-toggle-on"></i> <?= $supplier['is_active'] ? 'Deactivate' : 'Activate' ?> Supplier
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Audit Trail</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><small class="text-muted">Created:</small><br><?= date('M d, Y H:i', strtotime($supplier['created_at'])) ?></p>
                    <?php if ($supplier['updated_at']): ?>
                    <p class="mb-0"><small class="text-muted">Updated:</small><br><?= date('M d, Y H:i', strtotime($supplier['updated_at'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this supplier?`)) return;
    
    try {
        await Ajax.put(`/api/erp/suppliers/${id}`, { is_active: currentStatus ? 0 : 1 });
        UI.createAlert(`Supplier ${action}d successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert(`Failed to ${action} supplier`, 'danger');
    }
}
</script>
<?php $this->endSection() ?>
