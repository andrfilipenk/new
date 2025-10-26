<?php 
use Core\View\ViewEngine;
ViewEngine::extend('layouts/app'); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-shield-check"></i> Role Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="/admin/roles/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Role
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($roles)): ?>
                <p class="text-muted">No roles found</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?php echo $role->id; ?></td>
                                <td><?php echo htmlspecialchars($role->name); ?></td>
                                <td><?php echo htmlspecialchars($role->description ?? ''); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo count($role->permissions ?? []); ?> permissions</span>
                                </td>
                                <td>
                                    <a href="/admin/roles/<?php echo $role->id; ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
