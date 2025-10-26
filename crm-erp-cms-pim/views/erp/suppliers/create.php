<?php $this->layout('layouts/app', ['title' => 'Create Supplier']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Supplier</h1>
        <a href="/erp/suppliers" class="btn btn-secondary">
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
                                <input type="text" name="supplier_code" class="form-control" required>
                                <small class="text-muted">Unique identifier for the supplier</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Name *</label>
                                <input type="text" name="company_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact Person *</label>
                                <input type="text" name="contact_person" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State/Province</label>
                                <input type="text" name="state" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country *</label>
                                <input type="text" name="country" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tax ID</label>
                                <input type="text" name="tax_id" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Rating</label>
                                <select name="rating" class="form-select">
                                    <option value="0">No Rating</option>
                                    <option value="1">1 Star</option>
                                    <option value="2">2 Stars</option>
                                    <option value="3" selected>3 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="5">5 Stars</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Terms</label>
                                <select name="payment_terms" class="form-select">
                                    <option value="net_30">Net 30</option>
                                    <option value="net_60">Net 60</option>
                                    <option value="net_90">Net 90</option>
                                    <option value="due_on_receipt">Due on Receipt</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="JPY">JPY</option>
                                    <option value="CNY">CNY</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">Active Supplier</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/erp/suppliers" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <strong>Supplier Code:</strong> Must be unique. This will be used to identify the supplier in the system.
                    </p>
                    <p class="text-muted">
                        <strong>Rating:</strong> Rate the supplier based on past performance, quality, and reliability.
                    </p>
                    <p class="text-muted">
                        <strong>Payment Terms:</strong> Standard payment terms for this supplier.
                    </p>
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
    
    // Convert checkbox to proper boolean
    data.is_active = formData.has('is_active') ? 1 : 0;
    
    try {
        const response = await Ajax.post('/api/erp/suppliers', data);
        UI.createAlert('Supplier created successfully', 'success');
        setTimeout(() => {
            window.location.href = '/erp/suppliers/' + response.supplier.id;
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to create supplier', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
