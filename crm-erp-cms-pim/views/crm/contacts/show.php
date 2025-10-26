<?php $this->layout('layouts/app', ['title' => $contact['first_name'] . ' ' . $contact['last_name']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></h1>
        <div>
            <a href="/crm/contacts/<?= $contact['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/crm/contacts" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong> <?= htmlspecialchars($contact['email']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Title:</strong> <?= htmlspecialchars($contact['title'] ?? '-') ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Phone:</strong> <?= htmlspecialchars($contact['phone'] ?? '-') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Mobile:</strong> <?= htmlspecialchars($contact['mobile'] ?? '-') ?>
                        </div>
                    </div>
                    <?php if ($account): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Account:</strong> 
                            <a href="/crm/accounts/<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contact['linkedin']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>LinkedIn:</strong> 
                            <a href="<?= htmlspecialchars($contact['linkedin']) ?>" target="_blank"><?= htmlspecialchars($contact['linkedin']) ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contact['address']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <strong>Address:</strong><br>
                            <?= nl2br(htmlspecialchars($contact['address'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contact['notes']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($contact['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                    <a href="/crm/activities/create?related_type=contact&related_id=<?= $contact['id'] ?>" class="btn btn-sm btn-primary">
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Info</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Created:</strong> <?= date('M d, Y', strtotime($contact['created_at'])) ?>
                    </div>
                    <?php if ($contact['updated_at']): ?>
                    <div>
                        <strong>Last Updated:</strong> <?= date('M d, Y', strtotime($contact['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
