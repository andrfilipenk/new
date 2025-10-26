<?php $this->layout('layouts/app', ['title' => 'Opportunities']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Opportunities<?= isset($accountId) ? ' - Account Filter' : '' ?></h1>
        <div>
            <a href="/crm/opportunities/pipeline" class="btn btn-outline-primary me-2">
                <i class="bi bi-kanban"></i> Pipeline View
            </a>
            <a href="/crm/opportunities/create<?= isset($accountId) ? '?account_id=' . $accountId : '' ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Opportunity
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Account</th>
                            <th>Stage</th>
                            <th>Amount</th>
                            <th>Probability</th>
                            <th>Close Date</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($opportunities)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No opportunities found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($opportunities as $opp): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($opp['name']) ?></strong></td>
                            <td>
                                <?php if ($opp['account_id']): ?>
                                    <a href="/crm/accounts/<?= $opp['account_id'] ?>">View Account</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
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
                                $badge = $stageBadges[$opp['stage']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucfirst(str_replace('_', ' ', $opp['stage'])) ?>
                                </span>
                            </td>
                            <td><strong>$<?= number_format($opp['amount']) ?></strong></td>
                            <td><?= $opp['probability'] ?>%</td>
                            <td><?= date('M d, Y', strtotime($opp['close_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($opp['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/crm/opportunities/<?= $opp['id'] ?>" class="btn btn-outline-primary">View</a>
                                    <a href="/crm/opportunities/<?= $opp['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                    <button class="btn btn-outline-danger" onclick="deleteOpportunity(<?= $opp['id'] ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteOpportunity(id) {
    if (confirm('Are you sure you want to delete this opportunity?')) {
        Ajax.delete(`/api/crm/opportunities/${id}`)
            .then(() => {
                UI.createAlert('Opportunity deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to delete opportunity', 'danger');
            });
    }
}
</script>
<?php $this->endSection() ?>
