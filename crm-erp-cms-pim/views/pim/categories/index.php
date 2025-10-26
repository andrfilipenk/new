<?php $this->layout('layouts/app', ['title' => 'Product Categories']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Product Categories</h1>
        <a href="/pim/categories/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Category
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Categories</h6>
                    <h3><?= count($categories) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Root Categories</h6>
                    <h3><?= count(array_filter($categories, fn($c) => !$c['parent_id'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Active Categories</h6>
                    <h3><?= count(array_filter($categories, fn($c) => $c['is_active'])) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Products</h6>
                    <h3><?= array_sum(array_column($categories, 'product_count')) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Category Tree -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40%">Category Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No categories found. <a href="/pim/categories/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            // Build tree structure
                            function buildCategoryTree($categories, $parentId = null, $level = 0) {
                                $result = '';
                                foreach ($categories as $category) {
                                    if ($category['parent_id'] == $parentId) {
                                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                                        $icon = $level > 0 ? '└─ ' : '';
                                        
                                        $result .= '<tr>';
                                        $result .= '<td>' . $indent . $icon . '<strong>' . htmlspecialchars($category['name']) . '</strong></td>';
                                        $result .= '<td><code>' . htmlspecialchars($category['slug']) . '</code></td>';
                                        $result .= '<td>' . ($category['product_count'] ?? 0) . '</td>';
                                        $result .= '<td><span class="badge bg-' . ($category['is_active'] ? 'success' : 'secondary') . '">' . ($category['is_active'] ? 'Active' : 'Inactive') . '</span></td>';
                                        $result .= '<td>';
                                        $result .= '<div class="btn-group btn-group-sm">';
                                        $result .= '<a href="/pim/categories/' . $category['id'] . '" class="btn btn-outline-primary">View</a>';
                                        $result .= '<a href="/pim/categories/' . $category['id'] . '/edit" class="btn btn-outline-secondary">Edit</a>';
                                        $result .= '</div>';
                                        $result .= '</td>';
                                        $result .= '</tr>';
                                        
                                        // Recursively add children
                                        $result .= buildCategoryTree($categories, $category['id'], $level + 1);
                                    }
                                }
                                return $result;
                            }
                            
                            echo buildCategoryTree($categories);
                            ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
