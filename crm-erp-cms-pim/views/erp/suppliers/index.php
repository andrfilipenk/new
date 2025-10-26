<?php $this->layout('layouts/app', ['title' => 'Suppliers']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Suppliers</h1>
        <a href="/erp/suppliers/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Supplier
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Suppliers</h6>
                    <h3><?= count($suppliers) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Suppliers</h6>
                    <h3><?= count(array_filter($suppliers, fn($s) => $s['is_active'] == 1)) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Inactive Suppliers</h6>
                    <h3><?= count(array_filter($suppliers, fn($s) => $s['is_active'] == 0)) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Avg Rating</h6>
                    <h3><?= number_format(array_sum(array_column($suppliers, 'rating')) / max(count($suppliers), 1), 1) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Suppliers Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Supplier Code</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No suppliers found. <a href="/erp/suppliers/create">Add one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($supplier['supplier_code']) ?></strong></td>
                                <td><?= htmlspecialchars($supplier['company_name']) ?></td>
                                <td><?= htmlspecialchars($supplier['contact_person']) ?></td>
                                <td><?= htmlspecialchars($supplier['email']) ?></td>
                                <td><?= htmlspecialchars($supplier['phone']) ?></td>
                                <td>
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <?php if ($i < $supplier['rating']): ?>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star text-muted"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $supplier['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $supplier['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/erp/suppliers/<?= $supplier['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <a href="/erp/suppliers/<?= $supplier['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
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
