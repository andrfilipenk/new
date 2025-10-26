<?php $this->layout('layouts/app', ['title' => $opportunity['name']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($opportunity['name']) ?></h1>
        <div>
            <button class="btn btn-success" onclick="markAsWon(<?= $opportunity['id'] ?>)">
                <i class="bi bi-check-circle"></i> Mark Won
            </button>
            <button class="btn btn-danger" onclick="markAsLost(<?= $opportunity['id'] ?>)">
                <i class="bi bi-x-circle"></i> Mark Lost
            </button>
            <a href="/crm/opportunities/<?= $opportunity['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/crm/opportunities" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Opportunity Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Amount:</strong> <span class="text-success fs-4">$<?= number_format($opportunity['amount']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Stage:</strong> 
                            <?php
                            $stageBadges = [
                                'prospecting' => 'secondary',
                                'qualification' => 'info',
                                'needs_analysis' => 'primary',
                                'proposal' => 'warning',
                                'negotiation' => 'success',
                                'closed_won' => 'success',
                                'closed_lost' => 'danger',
                            ];
                            $badge = $stageBadges[$opportunity['stage']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $badge ?>">
                                <?= ucfirst(str_replace('_', ' ', $opportunity['stage'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Probability:</strong> <?= $opportunity['probability'] ?>%
                            <div class="progress mt-1">
                                <div class="progress-bar" role="progressbar" style="width: <?= $opportunity['probability'] ?>%" 
                                     aria-valuenow="<?= $opportunity['probability'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <strong>Close Date:</strong> <?= date('M d, Y', strtotime($opportunity['close_date'])) ?>
                        </div>
                    </div>
                    <?php if ($account): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Account:</strong> 
                            <a href="/crm/accounts/<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></a>
                        </div>
                        <?php if ($contact): ?>
                        <div class="col-md-6">
                            <strong>Contact:</strong> 
                            <a href="/crm/contacts/<?= $contact['id'] ?>"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($opportunity['type']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong> <?= htmlspecialchars($opportunity['type']) ?>
                        </div>
                        <?php if ($opportunity['lead_source']): ?>
                        <div class="col-md-6">
                            <strong>Lead Source:</strong> <?= htmlspecialchars($opportunity['lead_source']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($opportunity['description']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Description:</strong><br>
                            <?= nl2br(htmlspecialchars($opportunity['description'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($opportunity['next_steps']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Next Steps:</strong><br>
                            <?= nl2br(htmlspecialchars($opportunity['next_steps'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Activity Timeline</h5>
                    <a href="/crm/activities/create?related_type=opportunity&related_id=<?= $opportunity['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Activity
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-muted">No activities recorded yet.</p>
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
                    <h5 class="card-title mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Weighted Value:</strong><br>
                        $<?= number_format($opportunity['amount'] * $opportunity['probability'] / 100) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong><br>
                        <?= date('M d, Y', strtotime($opportunity['created_at'])) ?>
                    </div>
                    <?php if ($opportunity['updated_at']): ?>
                    <div>
                        <strong>Last Updated:</strong><br>
                        <?= date('M d, Y', strtotime($opportunity['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stage Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="updateStage('prospecting')">Move to Prospecting</button>
                        <button class="btn btn-outline-info btn-sm" onclick="updateStage('qualification')">Move to Qualification</button>
                        <button class="btn btn-outline-primary btn-sm" onclick="updateStage('needs_analysis')">Move to Needs Analysis</button>
                        <button class="btn btn-outline-warning btn-sm" onclick="updateStage('proposal')">Move to Proposal</button>
                        <button class="btn btn-outline-success btn-sm" onclick="updateStage('negotiation')">Move to Negotiation</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsWon(id) {
    if (confirm('Mark this opportunity as won?')) {
        Ajax.post(`/api/crm/opportunities/${id}/won`)
            .then(() => {
                UI.createAlert('Opportunity marked as won!', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to update opportunity', 'danger');
            });
    }
}

function markAsLost(id) {
    const reason = prompt('Please enter the reason for losing this opportunity:');
    if (reason) {
        Ajax.post(`/api/crm/opportunities/${id}/lost`, { reason })
            .then(() => {
                UI.createAlert('Opportunity marked as lost', 'info');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to update opportunity', 'danger');
            });
    }
}

function updateStage(stage) {
    Ajax.post(`/api/crm/opportunities/<?= $opportunity['id'] ?>/stage`, { stage })
        .then(() => {
            UI.createAlert('Stage updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            UI.createAlert('Failed to update stage', 'danger');
        });
}
</script>
<?php $this->endSection() ?>
