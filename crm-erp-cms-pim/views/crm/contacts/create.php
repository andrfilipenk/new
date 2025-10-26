<?php $this->layout('layouts/app', ['title' => isset($contact) ? 'Edit Contact' : 'Create Contact']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($contact) ? 'Edit Contact' : 'Create Contact' ?></h1>
        <a href="/crm/contacts" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Contacts
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= isset($contact) ? "/crm/contacts/{$contact['id']}" : '/crm/contacts' ?>">
                        <?php if (isset($contact)): ?>
                        <input type="hidden" name="_method" value="PUT">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($contact['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($contact['last_name'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($contact['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title/Position</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= htmlspecialchars($contact['title'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($contact['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Mobile</label>
                                <input type="tel" class="form-control" id="mobile" name="mobile" 
                                       value="<?= htmlspecialchars($contact['mobile'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="account_id" class="form-label">Account</label>
                            <select class="form-select" id="account_id" name="account_id">
                                <option value="">-- Select Account --</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?= $account['id'] ?>" 
                                        <?= (isset($contact['account_id']) && $contact['account_id'] == $account['id']) || 
                                            (isset($accountId) && $accountId == $account['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($account['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin" class="form-label">LinkedIn Profile</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                   value="<?= htmlspecialchars($contact['linkedin'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($contact['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($contact['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/crm/contacts" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?= isset($contact) ? 'Update Contact' : 'Create Contact' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
