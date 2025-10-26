<?php $this->layout('layouts/app', ['title' => $account['name']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($account['name']) ?></h1>
        <div>
            <a href="/crm/accounts/<?= $account['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/crm/accounts" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Account Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Type:</strong> 
                            <span class="badge bg-<?= $account['type'] === 'customer' ? 'success' : 'info' ?>">
                                <?= ucfirst($account['type'] ?? 'Other') ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Industry:</strong> <?= htmlspecialchars($account['industry'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Phone:</strong> <?= htmlspecialchars($account['phone'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> <?= htmlspecialchars($account['email'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Website:</strong> 
                            <?php if ($account['website']): ?>
                                <a href="<?= htmlspecialchars($account['website']) ?>" target="_blank"><?= htmlspecialchars($account['website']) ?></a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Annual Revenue:</strong> <?= $account['annual_revenue'] ? '$' . number_format($account['annual_revenue']) : '-' ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Employees:</strong> <?= $account['employees'] ? number_format($account['employees']) : '-' ?>
                        </div>
                    </div>
                    <?php if ($account['billing_address']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Billing Address:</strong><br>
                            <?= nl2br(htmlspecialchars($account['billing_address'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($account['shipping_address']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Shipping Address:</strong><br>
                            <?= nl2br(htmlspecialchars($account['shipping_address'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($account['description']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Description:</strong><br>
                            <?= nl2br(htmlspecialchars($account['description'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Contacts -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Contacts</h5>
                    <a href="/crm/contacts/create?account_id=<?= $account['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Contact
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($contacts)): ?>
                        <p class="text-muted">No contacts found for this account.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($contacts as $contact): ?>
                            <a href="/crm/contacts/<?= $contact['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></h6>
                                        <small><?= htmlspecialchars($contact['title'] ?? '') ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted"><?= htmlspecialchars($contact['email']) ?></small>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Opportunities -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Opportunities</h5>
                    <a href="/crm/opportunities/create?account_id=<?= $account['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add Opportunity
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($opportunities)): ?>
                        <p class="text-muted">No opportunities found for this account.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($opportunities as $opp): ?>
                            <a href="/crm/opportunities/<?= $opp['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($opp['name']) ?></h6>
                                        <small>Stage: <?= ucfirst(str_replace('_', ' ', $opp['stage'])) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong>$<?= number_format($opp['amount']) ?></strong><br>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($opp['close_date'])) ?></small>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Quick Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Total Contacts:</strong> <?= count($contacts) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Total Opportunities:</strong> <?= count($opportunities) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Created:</strong> <?= date('M d, Y', strtotime($account['created_at'])) ?>
                    </div>
                    <?php if ($account['updated_at']): ?>
                    <div>
                        <strong>Last Updated:</strong> <?= date('M d, Y', strtotime($account['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
