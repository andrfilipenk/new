<?php $this->layout('layouts/app', ['title' => 'Price Lists']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Price Lists</h1>
        <a href="/pim/price-lists/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Price List
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Price Lists</h6>
                    <h3><?= count($priceLists) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Active</h6>
                    <h3><?= count(array_filter($priceLists, fn($p) => $p['is_active'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Current</h6>
                    <h3><?= count(array_filter($priceLists, function($p) {
                        $now = date('Y-m-d');
                        return $p['is_active'] && 
                               (!$p['valid_from'] || $p['valid_from'] <= $now) &&
                               (!$p['valid_to'] || $p['valid_to'] >= $now);
                    })) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Scheduled</h6>
                    <h3><?= count(array_filter($priceLists, fn($p) => $p['valid_from'] > date('Y-m-d'))) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Price Lists Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Currency</th>
                            <th>Valid From</th>
                            <th>Valid To</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($priceLists)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No price lists found. <a href="/pim/price-lists/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($priceLists as $priceList): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($priceList['name']) ?></strong></td>
                                <td><?= htmlspecialchars($priceList['currency']) ?></td>
                                <td><?= $priceList['valid_from'] ? date('M d, Y', strtotime($priceList['valid_from'])) : '-' ?></td>
                                <td><?= $priceList['valid_to'] ? date('M d, Y', strtotime($priceList['valid_to'])) : '-' ?></td>
                                <td><?= $priceList['item_count'] ?? 0 ?></td>
                                <td>
                                    <?php
                                    $now = date('Y-m-d');
                                    $status = 'inactive';
                                    $badge = 'secondary';
                                    
                                    if ($priceList['is_active']) {
                                        if ($priceList['valid_from'] && $priceList['valid_from'] > $now) {
                                            $status = 'scheduled';
                                            $badge = 'warning';
                                        } elseif ($priceList['valid_to'] && $priceList['valid_to'] < $now) {
                                            $status = 'expired';
                                            $badge = 'danger';
                                        } else {
                                            $status = 'active';
                                            $badge = 'success';
                                        }
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= ucfirst($status) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/pim/price-lists/<?= $priceList['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <a href="/pim/price-lists/<?= $priceList['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
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
