<?php $this->layout('layouts/app', ['title' => 'Accounts']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Accounts</h1>
        <a href="/crm/accounts/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Account
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search accounts..." value="<?= $_GET['search'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="prospect">Prospect</option>
                        <option value="customer">Customer</option>
                        <option value="partner">Partner</option>
                        <option value="vendor">Vendor</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="industry" class="form-select">
                        <option value="">All Industries</option>
                        <option value="technology">Technology</option>
                        <option value="finance">Finance</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="retail">Retail</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="/crm/accounts" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Accounts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Industry</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Annual Revenue</th>
                            <th>Employees</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No accounts found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($account['name']) ?></strong></td>
                            <td>
                                <span class="badge bg-<?= $account['type'] === 'customer' ? 'success' : 'info' ?>">
                                    <?= ucfirst($account['type'] ?? 'Other') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($account['industry'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($account['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($account['email'] ?? '-') ?></td>
                            <td><?= $account['annual_revenue'] ? '$' . number_format($account['annual_revenue']) : '-' ?></td>
                            <td><?= $account['employees'] ? number_format($account['employees']) : '-' ?></td>
                            <td><?= date('M d, Y', strtotime($account['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/crm/accounts/<?= $account['id'] ?>" class="btn btn-outline-primary">View</a>
                                    <a href="/crm/accounts/<?= $account['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                    <button class="btn btn-outline-danger" onclick="deleteAccount(<?= $account['id'] ?>)">Delete</button>
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

<script>
function deleteAccount(id) {
    if (confirm('Are you sure you want to delete this account?')) {
        Ajax.delete(`/api/crm/accounts/${id}`)
            .then(() => {
                UI.createAlert('Account deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert(error.error || 'Failed to delete account', 'danger');
            });
    }
}
</script>
<?php $this->endSection() ?>
