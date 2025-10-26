<?php $this->layout('layouts/app', ['title' => 'Products']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Product Catalog</h1>
        <a href="/pim/products/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Product
        </a>
    </div>
    
    <!-- Search & Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?= $_GET['q'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="min_price" class="form-control" placeholder="Min Price">
                </div>
                <div class="col-md-2">
                    <input type="number" name="max_price" class="form-control" placeholder="Max Price">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="row">
        <?php foreach ($products as $product): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                    <p class="text-muted mb-1">SKU: <?= htmlspecialchars($product['sku']) ?></p>
                    <h4 class="text-primary">$<?= number_format($product['price'], 2) ?></h4>
                    <p class="mb-2">
                        <small class="text-muted">Stock: <?= $product['stock_quantity'] ?></small>
                    </p>
                    <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= ucfirst($product['status']) ?>
                    </span>
                </div>
                <div class="card-footer">
                    <div class="btn-group btn-group-sm w-100">
                        <a href="/pim/products/<?= $product['id'] ?>" class="btn btn-outline-primary">View</a>
                        <a href="/pim/products/<?= $product['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php $this->endSection() ?>
