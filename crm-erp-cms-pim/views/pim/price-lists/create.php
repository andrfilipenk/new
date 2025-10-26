<?php $this->layout('layouts/app', ['title' => 'Create Price List']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Price List</h1>
        <a href="/pim/price-lists" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Price List Information</h5>
                </div>
                <div class="card-body">
                    <form id="priceListForm">
                        <div class="mb-3">
                            <label class="form-label">Price List Name *</label>
                            <input type="text" name="name" class="form-control" required>
                            <small class="text-muted">E.g., "Wholesale 2025", "Retail Black Friday"</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Currency *</label>
                                <select name="currency" class="form-select" required>
                                    <option value="USD">USD - US Dollar</option>
                                    <option value="EUR">EUR - Euro</option>
                                    <option value="GBP">GBP - British Pound</option>
                                    <option value="JPY">JPY - Japanese Yen</option>
                                    <option value="CNY">CNY - Chinese Yuan</option>
                                    <option value="CAD">CAD - Canadian Dollar</option>
                                    <option value="AUD">AUD - Australian Dollar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer Group</label>
                                <input type="text" name="customer_group" class="form-control">
                                <small class="text-muted">Optional: retail, wholesale, etc.</small>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Valid From</label>
                                <input type="date" name="valid_from" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valid To</label>
                                <input type="date" name="valid_to" class="form-control">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">Active Price List</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/price-lists" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Price List</button>
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
                        <strong>Price Lists</strong> allow you to set different prices for products based on customer groups, regions, or time periods.
                    </p>
                    <p class="text-muted">
                        <strong>Valid From/To:</strong> Set date ranges for promotional or seasonal pricing.
                    </p>
                    <p class="text-muted">
                        <strong>Currency:</strong> All prices in this list will use the selected currency.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('priceListForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.is_active = formData.has('is_active') ? 1 : 0;
    
    try {
        const response = await Ajax.post('/api/pim/price-lists', data);
        UI.createAlert('Price list created successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/price-lists/' + response.priceList.id;
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to create price list', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
