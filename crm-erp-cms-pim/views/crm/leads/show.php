<?php $this->layout('layouts/app', ['title' => $lead['first_name'] . ' ' . $lead['last_name']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></h1>
        <div>
            <button class="btn btn-success" onclick="convertLead()">
                <i class="bi bi-arrow-right-circle"></i> Convert Lead
            </button>
            <a href="/crm/leads/<?= $lead['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/crm/leads" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Lead Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Name:</strong> <?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Company:</strong> <?= htmlspecialchars($lead['company'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong> <?= htmlspecialchars($lead['email']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong> <?= htmlspecialchars($lead['phone'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Title:</strong> <?= htmlspecialchars($lead['title'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Source:</strong> 
                            <span class="badge bg-info"><?= ucfirst($lead['source'] ?? 'Unknown') ?></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?= $lead['status'] === 'converted' ? 'success' : ($lead['status'] === 'qualified' ? 'primary' : 'secondary') ?>">
                                <?= ucfirst($lead['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Score:</strong> <?= $lead['score'] ?? 0 ?> / 100
                        </div>
                    </div>
                    <?php if ($lead['website']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Website:</strong> 
                            <a href="<?= htmlspecialchars($lead['website']) ?>" target="_blank"><?= htmlspecialchars($lead['website']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($lead['linkedin']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>LinkedIn:</strong> 
                            <a href="<?= htmlspecialchars($lead['linkedin']) ?>" target="_blank"><?= htmlspecialchars($lead['linkedin']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($lead['address']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Address:</strong><br>
                            <?= nl2br(htmlspecialchars($lead['address'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($lead['notes']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($lead['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                    <a href="/crm/activities/create?related_type=lead&related_id=<?= $lead['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Activity
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-muted">No activities found.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($activity['subject']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($activity['due_date'] ?? $activity['created_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($activity['description']) ?></p>
                                <small>Type: <?= ucfirst($activity['type']) ?> | Status: <?= ucfirst($activity['status']) ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($lead['status'] === 'new'): ?>
                        <button class="btn btn-outline-primary" onclick="markContacted()">
                            <i class="bi bi-telephone"></i> Mark as Contacted
                        </button>
                        <?php endif; ?>
                        <?php if ($lead['status'] === 'contacted' || $lead['status'] === 'new'): ?>
                        <button class="btn btn-outline-success" onclick="qualifyLead()">
                            <i class="bi bi-check-circle"></i> Qualify Lead
                        </button>
                        <?php endif; ?>
                        <?php if ($lead['status'] === 'qualified'): ?>
                        <button class="btn btn-success" onclick="convertLead()">
                            <i class="bi bi-arrow-right-circle"></i> Convert to Contact
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary" onclick="assignLead()">
                            <i class="bi bi-person-plus"></i> Assign Lead
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Info</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Created:</strong> <?= date('M d, Y', strtotime($lead['created_at'])) ?>
                    </div>
                    <?php if ($lead['updated_at']): ?>
                    <div class="mb-3">
                        <strong>Last Updated:</strong> <?= date('M d, Y', strtotime($lead['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($lead['assigned_to']): ?>
                    <div>
                        <strong>Assigned To:</strong> <?= htmlspecialchars($lead['assigned_to_name'] ?? 'User #' . $lead['assigned_to']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function markContacted() {
    try {
        await Ajax.put(`/api/crm/leads/<?= $lead['id'] ?>`, { status: 'contacted' });
        UI.createAlert('Lead marked as contacted', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to update lead status', 'danger');
    }
}

async function qualifyLead() {
    try {
        await Ajax.post(`/api/crm/leads/<?= $lead['id'] ?>/qualify`, {});
        UI.createAlert('Lead qualified successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to qualify lead', 'danger');
    }
}

async function convertLead() {
    if (!confirm('Are you sure you want to convert this lead to a contact? This action cannot be undone.')) {
        return;
    }
    
    try {
        const result = await Ajax.post(`/api/crm/leads/<?= $lead['id'] ?>/convert`, {});
        UI.createAlert('Lead converted successfully', 'success');
        setTimeout(() => window.location.href = '/crm/contacts/' + result.contact.id, 1500);
    } catch (error) {
        UI.createAlert('Failed to convert lead', 'danger');
    }
}

async function assignLead() {
    const userId = prompt('Enter user ID to assign this lead to:');
    if (!userId) return;
    
    try {
        await Ajax.post(`/api/crm/leads/<?= $lead['id'] ?>/assign`, { assigned_to: userId });
        UI.createAlert('Lead assigned successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to assign lead', 'danger');
    }
}
</script>
<?php $this->endSection() ?>
