<?php $this->layout('layouts/app', ['title' => isset($opportunity) ? 'Edit Opportunity' : 'Create Opportunity']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($opportunity) ? 'Edit Opportunity' : 'Create Opportunity' ?></h1>
        <a href="/crm/opportunities" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= isset($opportunity) ? "/crm/opportunities/{$opportunity['id']}" : '/crm/opportunities' ?>">
                        <?php if (isset($opportunity)): ?>
                        <input type="hidden" name="_method" value="PUT">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Opportunity Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($opportunity['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Account</label>
                                <select class="form-select" id="account_id" name="account_id">
                                    <option value="">-- Select Account --</option>
                                    <?php foreach ($accounts as $account): ?>
                                    <option value="<?= $account['id'] ?>" 
                                            <?= (isset($opportunity['account_id']) && $opportunity['account_id'] == $account['id']) || 
                                                (isset($accountId) && $accountId == $account['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($account['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_id" class="form-label">Contact</label>
                                <select class="form-select" id="contact_id" name="contact_id">
                                    <option value="">-- Select Contact --</option>
                                    <?php foreach ($contacts as $contact): ?>
                                    <option value="<?= $contact['id'] ?>" 
                                            <?= (isset($opportunity['contact_id']) && $opportunity['contact_id'] == $contact['id']) || 
                                                (isset($contactId) && $contactId == $contact['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount *</label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       value="<?= htmlspecialchars($opportunity['amount'] ?? '') ?>" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="close_date" class="form-label">Close Date *</label>
                                <input type="date" class="form-control" id="close_date" name="close_date" 
                                       value="<?= htmlspecialchars($opportunity['close_date'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stage" class="form-label">Stage *</label>
                                <select class="form-select" id="stage" name="stage" required>
                                    <option value="prospecting" <?= ($opportunity['stage'] ?? '') === 'prospecting' ? 'selected' : '' ?>>Prospecting</option>
                                    <option value="qualification" <?= ($opportunity['stage'] ?? '') === 'qualification' ? 'selected' : '' ?>>Qualification</option>
                                    <option value="needs_analysis" <?= ($opportunity['stage'] ?? '') === 'needs_analysis' ? 'selected' : '' ?>>Needs Analysis</option>
                                    <option value="proposal" <?= ($opportunity['stage'] ?? '') === 'proposal' ? 'selected' : '' ?>>Proposal</option>
                                    <option value="negotiation" <?= ($opportunity['stage'] ?? '') === 'negotiation' ? 'selected' : '' ?>>Negotiation</option>
                                    <option value="closed_won" <?= ($opportunity['stage'] ?? '') === 'closed_won' ? 'selected' : '' ?>>Closed Won</option>
                                    <option value="closed_lost" <?= ($opportunity['stage'] ?? '') === 'closed_lost' ? 'selected' : '' ?>>Closed Lost</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="probability" class="form-label">Probability (%)</label>
                                <input type="number" class="form-control" id="probability" name="probability" 
                                       value="<?= htmlspecialchars($opportunity['probability'] ?? '50') ?>" min="0" max="100">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type</label>
                                <input type="text" class="form-control" id="type" name="type" 
                                       value="<?= htmlspecialchars($opportunity['type'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="lead_source" class="form-label">Lead Source</label>
                                <input type="text" class="form-control" id="lead_source" name="lead_source" 
                                       value="<?= htmlspecialchars($opportunity['lead_source'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($opportunity['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="next_steps" class="form-label">Next Steps</label>
                            <textarea class="form-control" id="next_steps" name="next_steps" rows="2"><?= htmlspecialchars($opportunity['next_steps'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/crm/opportunities" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?= isset($opportunity) ? 'Update Opportunity' : 'Create Opportunity' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
