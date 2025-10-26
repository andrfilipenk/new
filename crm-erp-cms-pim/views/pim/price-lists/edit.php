<?php $this->layout('layouts/app', ['title' => 'Edit Price List']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Price List</h1>
        <a href="/pim/price-lists/<?= $priceList['id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="priceListForm">
                        <div class="mb-3">
                            <label class="form-label">Price List Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($priceList['name']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Currency *</label>
                                <select name="currency" class="form-select" required>
                                    <option value="USD" <?= $priceList['currency'] === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                    <option value="EUR" <?= $priceList['currency'] === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                    <option value="GBP" <?= $priceList['currency'] === 'GBP' ? 'selected' : '' ?>>GBP - British Pound</option>
                                    <option value="JPY" <?= $priceList['currency'] === 'JPY' ? 'selected' : '' ?>>JPY - Japanese Yen</option>
                                    <option value="CNY" <?= $priceList['currency'] === 'CNY' ? 'selected' : '' ?>>CNY - Chinese Yuan</option>
                                    <option value="CAD" <?= $priceList['currency'] === 'CAD' ? 'selected' : '' ?>>CAD - Canadian Dollar</option>
                                    <option value="AUD" <?= $priceList['currency'] === 'AUD' ? 'selected' : '' ?>>AUD - Australian Dollar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Customer Group</label>
                                <input type="text" name="customer_group" class="form-control" value="<?= htmlspecialchars($priceList['customer_group'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Valid From</label>
                                <input type="date" name="valid_from" class="form-control" value="<?= $priceList['valid_from'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valid To</label>
                                <input type="date" name="valid_to" class="form-control" value="<?= $priceList['valid_to'] ?? '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($priceList['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" <?= $priceList['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active Price List</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/price-lists/<?= $priceList['id'] ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Price List</button>
                        </div>
                    </form>
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
        await Ajax.put('/api/pim/price-lists/<?= $priceList['id'] ?>', data);
        UI.createAlert('Price list updated successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/price-lists/<?= $priceList['id'] ?>';
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to update price list', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
