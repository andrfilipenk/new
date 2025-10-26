<?php $this->layout('layouts/app', ['title' => 'Sales Pipeline']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Sales Pipeline</h1>
        <div>
            <a href="/crm/opportunities" class="btn btn-outline-secondary me-2">
                <i class="bi bi-list"></i> List View
            </a>
            <a href="/crm/opportunities/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> New Opportunity
            </a>
        </div>
    </div>
    
    <div class="row">
        <?php
        $stages = [
            'prospecting' => ['label' => 'Prospecting', 'color' => 'secondary'],
            'qualification' => ['label' => 'Qualification', 'color' => 'info'],
            'needs_analysis' => ['label' => 'Needs Analysis', 'color' => 'primary'],
            'proposal' => ['label' => 'Proposal', 'color' => 'warning'],
            'negotiation' => ['label' => 'Negotiation', 'color' => 'success'],
            'closed_won' => ['label' => 'Closed Won', 'color' => 'success'],
            'closed_lost' => ['label' => 'Closed Lost', 'color' => 'danger'],
        ];
        
        foreach ($stages as $stageKey => $stageInfo):
            $opps = $pipeline[$stageKey] ?? [];
            $totalValue = array_sum(array_column($opps, 'amount'));
        ?>
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header bg-<?= $stageInfo['color'] ?> text-white">
                    <h6 class="mb-0"><?= $stageInfo['label'] ?></h6>
                    <small><?= count($opps) ?> opportunities | $<?= number_format($totalValue) ?></small>
                </div>
                <div class="card-body p-2" style="min-height: 400px; max-height: 600px; overflow-y: auto;">
                    <?php if (empty($opps)): ?>
                        <p class="text-muted text-center mt-3">No opportunities</p>
                    <?php else: ?>
                        <?php foreach ($opps as $opp): ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body p-2">
                                <a href="/crm/opportunities/<?= $opp['id'] ?>" class="text-decoration-none">
                                    <h6 class="mb-1"><?= htmlspecialchars($opp['name']) ?></h6>
                                </a>
                                <p class="mb-1 small text-muted"><?= htmlspecialchars($opp['account_id'] ? 'Account #' . $opp['account_id'] : '-') ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-success">$<?= number_format($opp['amount']) ?></strong>
                                    <span class="badge bg-light text-dark"><?= $opp['probability'] ?>%</span>
                                </div>
                                <small class="text-muted">Close: <?= date('M d', strtotime($opp['close_date'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.card-body a:hover {
    text-decoration: underline !important;
}
</style>
<?php $this->endSection() ?>
