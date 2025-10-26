<?php $this->layout('layouts/app', ['title' => 'Product Attributes']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Product Attributes</h1>
        <a href="/pim/attributes/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Attribute
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Attributes</h6>
                    <h3><?= count($attributes) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Required</h6>
                    <h3><?= count(array_filter($attributes, fn($a) => $a['is_required'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Searchable</h6>
                    <h3><?= count(array_filter($attributes, fn($a) => $a['is_searchable'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Filterable</h6>
                    <h3><?= count(array_filter($attributes, fn($a) => $a['is_filterable'])) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attributes Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Attribute Code</th>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Searchable</th>
                            <th>Filterable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attributes)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No attributes found. <a href="/pim/attributes/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($attributes as $attribute): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($attribute['code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($attribute['label']) ?></strong></td>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'text' => 'bi-fonts',
                                        'textarea' => 'bi-textarea-t',
                                        'number' => 'bi-123',
                                        'decimal' => 'bi-currency-dollar',
                                        'date' => 'bi-calendar',
                                        'boolean' => 'bi-toggle-on',
                                        'select' => 'bi-list-ul',
                                        'multiselect' => 'bi-list-check'
                                    ];
                                    $icon = $typeIcons[$attribute['type']] ?? 'bi-question';
                                    ?>
                                    <i class="bi <?= $icon ?>"></i> <?= ucfirst($attribute['type']) ?>
                                </td>
                                <td>
                                    <?php if ($attribute['is_required']): ?>
                                        <span class="badge bg-danger">Required</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Optional</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attribute['is_searchable']): ?>
                                        <i class="bi bi-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attribute['is_filterable']): ?>
                                        <i class="bi bi-check-circle text-success"></i>
                                    <?php else: ?>
                                        <i class="bi bi-x-circle text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/pim/attributes/<?= $attribute['id'] ?>/edit" class="btn btn-outline-primary">Edit</a>
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
<?php $this->endSection() ?>
