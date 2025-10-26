<?php $this->layout('layouts/app', ['title' => isset($account) ? 'Edit Account' : 'Create Account']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($account) ? 'Edit Account' : 'Create Account' ?></h1>
        <a href="/crm/accounts" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Accounts
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= isset($account) ? "/crm/accounts/{$account['id']}" : '/crm/accounts' ?>" id="accountForm">
                        <?php if (isset($account)): ?>
                        <input type="hidden" name="_method" value="PUT">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Account Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($account['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="prospect" <?= ($account['type'] ?? '') === 'prospect' ? 'selected' : '' ?>>Prospect</option>
                                    <option value="customer" <?= ($account['type'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                                    <option value="partner" <?= ($account['type'] ?? '') === 'partner' ? 'selected' : '' ?>>Partner</option>
                                    <option value="vendor" <?= ($account['type'] ?? '') === 'vendor' ? 'selected' : '' ?>>Vendor</option>
                                    <option value="other" <?= ($account['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="industry" class="form-label">Industry</label>
                                <input type="text" class="form-control" id="industry" name="industry" 
                                       value="<?= htmlspecialchars($account['industry'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" id="website" name="website" 
                                       value="<?= htmlspecialchars($account['website'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($account['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($account['email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="annual_revenue" class="form-label">Annual Revenue</label>
                                <input type="number" class="form-control" id="annual_revenue" name="annual_revenue" 
                                       value="<?= htmlspecialchars($account['annual_revenue'] ?? '') ?>" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label for="employees" class="form-label">Number of Employees</label>
                                <input type="number" class="form-control" id="employees" name="employees" 
                                       value="<?= htmlspecialchars($account['employees'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="billing_address" class="form-label">Billing Address</label>
                            <textarea class="form-control" id="billing_address" name="billing_address" rows="2"><?= htmlspecialchars($account['billing_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="2"><?= htmlspecialchars($account['shipping_address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($account['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/crm/accounts" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?= isset($account) ? 'Update Account' : 'Create Account' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
