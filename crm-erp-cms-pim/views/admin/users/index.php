<?php 
use Core\View\ViewEngine;
ViewEngine::extend('layouts/app'); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-people-fill"></i> User Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="/admin/users/create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New User
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <p class="text-muted">No users found</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Roles</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user->id; ?></td>
                                <td><?php echo htmlspecialchars($user->name); ?></td>
                                <td><?php echo htmlspecialchars($user->email); ?></td>
                                <td>
                                    <?php if (!empty($user->roles)): ?>
                                        <?php foreach ($user->roles as $role): ?>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($role->name); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user->created_at)); ?></td>
                                <td>
                                    <a href="/admin/users/<?php echo $user->id; ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
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
