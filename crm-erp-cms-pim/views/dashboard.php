<?php 
use Core\View\ViewEngine;
ViewEngine::extend('layouts/app'); 
ViewEngine::section('content');
?>

<div class="container-fluid">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <?php foreach ($_SESSION['flash'] as $type => $message): ?>
            <div class="alert alert-<?php echo $type === 'error' ? 'danger' : $type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($user->name); ?>! 👋</h2>
                            <p class="text-muted mb-0">Here's what's happening with your business today</p>
                        </div>
                        <div>
                            <?php 
                            $roles = $user->roles();
                            $roleName = !empty($roles) ? $roles[0]->name : 'User';
                            ?>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($roleName); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-3 p-3">
                                <i class="bi bi-person-plus text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Leads</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_leads']); ?></h3>
                            <?php if ($stats['total_leads'] == 0): ?>
                            <small class="text-muted"><a href="<?php echo url('/crm/leads/create'); ?>">Add your first lead</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-3 p-3">
                                <i class="bi bi-graph-up text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Opportunities</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_opportunities']); ?></h3>
                            <?php if ($stats['total_opportunities'] == 0): ?>
                            <small class="text-muted"><a href="<?php echo url('/crm/opportunities/create'); ?>">Create opportunity</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-3 p-3">
                                <i class="bi bi-currency-dollar text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <?php if ($stats['total_revenue'] == 0): ?>
                            <small class="text-muted">No revenue yet</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-3 p-3">
                                <i class="bi bi-list-check text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Tasks</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['pending_tasks']); ?></h3>
                            <?php if ($stats['pending_tasks'] > 0): ?>
                            <small class="text-warning"><a href="<?php echo url('/crm/activities'); ?>">View tasks</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary bg-gradient rounded-3 p-3">
                                <i class="bi bi-people text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Customers</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_customers'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-gradient rounded-3 p-3">
                                <i class="bi bi-box-seam text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Low Stock Items</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['low_stock_items'] ?? 0); ?></h3>
                            <?php if (($stats['low_stock_items'] ?? 0) > 0): ?>
                            <small class="text-danger"><a href="<?php echo url('/erp/inventory'); ?>">Check inventory</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-3 p-3">
                                <i class="bi bi-file-text text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Published Pages</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['published_pages'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-3 p-3">
                                <i class="bi bi-box text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Products</h6>
                            <h3 class="mb-0"><?php echo number_format($stats['total_products'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Modules -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Quick Access</h4>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- CRM Module -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-people-fill text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title mb-2">CRM</h5>
                    <p class="card-text text-muted small mb-3">Customer Relationship Management</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('/crm/leads'); ?>" class="btn btn-sm btn-outline-primary">Leads</a>
                        <a href="<?php echo url('/crm/contacts'); ?>" class="btn btn-sm btn-outline-primary">Contacts</a>
                        <a href="<?php echo url('/crm/accounts'); ?>" class="btn btn-sm btn-outline-primary">Accounts</a>
                        <a href="<?php echo url('/crm/opportunities'); ?>" class="btn btn-sm btn-outline-primary">Opportunities</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ERP Module -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-building text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title mb-2">ERP</h5>
                    <p class="card-text text-muted small mb-3">Enterprise Resource Planning</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('/erp/inventory'); ?>" class="btn btn-sm btn-outline-success">Inventory</a>
                        <a href="<?php echo url('/erp/purchase-orders'); ?>" class="btn btn-sm btn-outline-success">Purchase Orders</a>
                        <a href="<?php echo url('/erp/invoices'); ?>" class="btn btn-sm btn-outline-success">Invoices</a>
                        <a href="<?php echo url('/erp/employees'); ?>" class="btn btn-sm btn-outline-success">Employees</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- CMS Module -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-text text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title mb-2">CMS</h5>
                    <p class="card-text text-muted small mb-3">Content Management System</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('/cms/pages'); ?>" class="btn btn-sm btn-outline-info">Pages</a>
                        <a href="<?php echo url('/cms/posts'); ?>" class="btn btn-sm btn-outline-info">Posts</a>
                        <a href="<?php echo url('/cms/media'); ?>" class="btn btn-sm btn-outline-info">Media</a>
                        <a href="<?php echo url('/cms/menus'); ?>" class="btn btn-sm btn-outline-info">Menus</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- PIM Module -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 hover-lift">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-box-seam text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="card-title mb-2">PIM</h5>
                    <p class="card-text text-muted small mb-3">Product Information Management</p>
                    <div class="d-grid gap-2">
                        <a href="<?php echo url('/pim/products'); ?>" class="btn btn-sm btn-outline-warning">Products</a>
                        <a href="<?php echo url('/pim/categories'); ?>" class="btn btn-sm btn-outline-warning">Categories</a>
                        <a href="<?php echo url('/pim/attributes'); ?>" class="btn btn-sm btn-outline-warning">Attributes</a>
                        <a href="<?php echo url('/pim/price-lists'); ?>" class="btn btn-sm btn-outline-warning">Price Lists</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">No recent activity to display</p>
                </div>
            </div>
        </div>
    </div>

    <style>
    .hover-lift {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    </style>
</div>

<?php ViewEngine::endSection(); ?>
