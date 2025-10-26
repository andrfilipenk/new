<?php $this->layout('layouts/app', ['title' => 'Category Details']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= htmlspecialchars($category['name']) ?></h1>
        <div>
            <a href="/pim/categories/<?= $category['id'] ?>/edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="/pim/categories" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Category Name</th>
                            <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Slug</th>
                            <td><code><?= htmlspecialchars($category['slug']) ?></code></td>
                        </tr>
                        <tr>
                            <th>Parent Category</th>
                            <td>
                                <?php if ($category['parent_id']): ?>
                                    <a href="/pim/categories/<?= $category['parent_id'] ?>">View Parent</a>
                                <?php else: ?>
                                    Root Category
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?= nl2br(htmlspecialchars($category['description'] ?? '-')) ?></td>
                        </tr>
                        <tr>
                            <th>Display Order</th>
                            <td><?= $category['sort_order'] ?? 0 ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                    <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($category['image']): ?>
                        <tr>
                            <th>Image</th>
                            <td><img src="<?= htmlspecialchars($category['image']) ?>" alt="Category" style="max-width: 200px;"></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($category['meta_title']): ?>
                        <tr>
                            <th>Meta Title</th>
                            <td><?= htmlspecialchars($category['meta_title']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($category['meta_description']): ?>
                        <tr>
                            <th>Meta Description</th>
                            <td><?= htmlspecialchars($category['meta_description']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Subcategories -->
            <?php if (!empty($subcategories)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Subcategories (<?= count($subcategories) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($subcategories as $sub): ?>
                        <a href="/pim/categories/<?= $sub['id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($sub['name']) ?></span>
                                <span class="badge bg-primary"><?= $sub['product_count'] ?? 0 ?> products</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products -->
            <?php if (!empty($products)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Products (<?= count($products) ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td>$<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $product['stock_quantity'] ?? 0 ?></td>
                                    <td>
                                        <a href="/pim/products/<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/pim/categories/create?parent_id=<?= $category['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Subcategory
                        </a>
                        <a href="/pim/products/create?category_id=<?= $category['id'] ?>" class="btn btn-success">
                            <i class="bi bi-box"></i> Add Product
                        </a>
                        <button class="btn btn-outline-secondary" onclick="toggleStatus(<?= $category['id'] ?>, <?= $category['is_active'] ?>)">
                            <i class="bi bi-toggle-on"></i> <?= $category['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Total Products:</strong> <?= $category['product_count'] ?? 0 ?></p>
                    <p class="mb-2"><strong>Subcategories:</strong> <?= count($subcategories ?? []) ?></p>
                    <p class="mb-0"><strong>Level:</strong> <?= $category['level'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function toggleStatus(id, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this category?`)) return;
    
    try {
        await Ajax.put(`/api/pim/categories/${id}`, { is_active: currentStatus ? 0 : 1 });
        UI.createAlert(`Category ${action}d successfully`, 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert(`Failed to ${action} category`, 'danger');
    }
}
</script>
<?php $this->endSection() ?>
