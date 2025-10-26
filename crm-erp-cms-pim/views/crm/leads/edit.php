<?php $this->layout('layouts/app', ['title' => 'Edit Lead']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Lead</h1>
        <a href="/crm/leads/<?= $lead['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Lead
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="leadForm" method="POST" action="/crm/leads/<?= $lead['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($lead['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($lead['last_name'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company" class="form-label">Company *</label>
                            <input type="text" class="form-control" id="company" name="company" 
                                   value="<?= htmlspecialchars($lead['company'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($lead['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($lead['phone'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title/Position</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($lead['title'] ?? '') ?>">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="source" class="form-label">Source *</label>
                                <select class="form-select" id="source" name="source" required>
                                    <option value="">Select Source</option>
                                    <option value="website" <?= ($lead['source'] ?? '') === 'website' ? 'selected' : '' ?>>Website</option>
                                    <option value="referral" <?= ($lead['source'] ?? '') === 'referral' ? 'selected' : '' ?>>Referral</option>
                                    <option value="advertising" <?= ($lead['source'] ?? '') === 'advertising' ? 'selected' : '' ?>>Advertising</option>
                                    <option value="trade_show" <?= ($lead['source'] ?? '') === 'trade_show' ? 'selected' : '' ?>>Trade Show</option>
                                    <option value="cold_call" <?= ($lead['source'] ?? '') === 'cold_call' ? 'selected' : '' ?>>Cold Call</option>
                                    <option value="email_campaign" <?= ($lead['source'] ?? '') === 'email_campaign' ? 'selected' : '' ?>>Email Campaign</option>
                                    <option value="social_media" <?= ($lead['source'] ?? '') === 'social_media' ? 'selected' : '' ?>>Social Media</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="new" <?= ($lead['status'] ?? '') === 'new' ? 'selected' : '' ?>>New</option>
                                    <option value="contacted" <?= ($lead['status'] ?? '') === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                    <option value="qualified" <?= ($lead['status'] ?? '') === 'qualified' ? 'selected' : '' ?>>Qualified</option>
                                    <option value="unqualified" <?= ($lead['status'] ?? '') === 'unqualified' ? 'selected' : '' ?>>Unqualified</option>
                                    <option value="converted" <?= ($lead['status'] ?? '') === 'converted' ? 'selected' : '' ?>>Converted</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="score" class="form-label">Lead Score (0-100)</label>
                                <input type="number" class="form-control" id="score" name="score" 
                                       value="<?= htmlspecialchars($lead['score'] ?? '0') ?>" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_to" class="form-label">Assigned To</label>
                                <select class="form-select" id="assigned_to" name="assigned_to">
                                    <option value="">-- Not Assigned --</option>
                                    <?php if (isset($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= (isset($lead['assigned_to']) && $lead['assigned_to'] == $user['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" 
                                   value="<?= htmlspecialchars($lead['website'] ?? '') ?>" placeholder="https://example.com">
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin" class="form-label">LinkedIn Profile</label>
                            <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                   value="<?= htmlspecialchars($lead['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/username">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($lead['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4"><?= htmlspecialchars($lead['notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/crm/leads/<?= $lead['id'] ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Lead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('leadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        await Ajax.put('/api/crm/leads/<?= $lead['id'] ?>', data);
        UI.createAlert('Lead updated successfully', 'success');
        setTimeout(() => window.location.href = '/crm/leads/<?= $lead['id'] ?>', 1000);
    } catch (error) {
        UI.createAlert('Failed to update lead', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
