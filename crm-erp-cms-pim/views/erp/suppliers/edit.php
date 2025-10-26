<?php $this->layout('layouts/app', ['title' => 'Edit Supplier']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Supplier</h1>
        <a href="/erp/suppliers/<?= $supplier['id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="supplierForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Supplier Code *</label>
                                <input type="text" name="supplier_code" class="form-control" value="<?= htmlspecialchars($supplier['supplier_code']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($supplier['company_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person *</label>
                                <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($supplier['email']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($supplier['website'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($supplier['address']) ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($supplier['city']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State/Province</label>
                                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($supplier['state'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country *</label>
                                <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($supplier['country']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($supplier['postal_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax ID</label>
                                <input type="text" name="tax_id" class="form-control" value="<?= htmlspecialchars($supplier['tax_id'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rating</label>
                                <select name="rating" class="form-select">
                                    <?php for ($i = 0; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= $supplier['rating'] == $i ? 'selected' : '' ?>><?= $i ?> Star<?= $i !== 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" class="form-select">
                                    <option value="net_30" <?= ($supplier['payment_terms'] ?? '') === 'net_30' ? 'selected' : '' ?>>Net 30</option>
                                    <option value="net_60" <?= ($supplier['payment_terms'] ?? '') === 'net_60' ? 'selected' : '' ?>>Net 60</option>
                                    <option value="net_90" <?= ($supplier['payment_terms'] ?? '') === 'net_90' ? 'selected' : '' ?>>Net 90</option>
                                    <option value="due_on_receipt" <?= ($supplier['payment_terms'] ?? '') === 'due_on_receipt' ? 'selected' : '' ?>>Due on Receipt</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="USD" <?= ($supplier['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                                    <option value="EUR" <?= ($supplier['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                                    <option value="GBP" <?= ($supplier['currency'] ?? '') === 'GBP' ? 'selected' : '' ?>>GBP</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" <?= $supplier['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active Supplier</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/erp/suppliers/<?= $supplier['id'] ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('supplierForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.is_active = formData.has('is_active') ? 1 : 0;
    
    try {
        await Ajax.put('/api/erp/suppliers/<?= $supplier['id'] ?>', data);
        UI.createAlert('Supplier updated successfully', 'success');
        setTimeout(() => {
            window.location.href = '/erp/suppliers/<?= $supplier['id'] ?>';
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to update supplier', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
