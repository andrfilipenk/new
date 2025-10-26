<?php $this->layout('layouts/app', ['title' => 'Leads']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Leads</h1>
        <a href="/crm/leads/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Lead
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search leads..." value="<?= $_GET['search'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="contacted">Contacted</option>
                        <option value="qualified">Qualified</option>
                        <option value="converted">Converted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="source" class="form-select">
                        <option value="">All Sources</option>
                        <option value="website">Website</option>
                        <option value="referral">Referral</option>
                        <option value="advertising">Advertising</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="/crm/leads" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Leads Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) ?></td>
                            <td><?= htmlspecialchars($lead['company'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($lead['email']) ?></td>
                            <td><?= htmlspecialchars($lead['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $lead['status'] === 'converted' ? 'success' : ($lead['status'] === 'qualified' ? 'primary' : 'secondary') ?>">
                                    <?= ucfirst($lead['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($lead['source'] ?? '-') ?></td>
                            <td><?= date('M d, Y', strtotime($lead['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/crm/leads/<?= $lead['id'] ?>" class="btn btn-outline-primary">View</a>
                                    <a href="/crm/leads/<?= $lead['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                    <button class="btn btn-outline-danger" onclick="deleteLead(<?= $lead['id'] ?>)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function deleteLead(id) {
    if (confirm('Are you sure you want to delete this lead?')) {
        Ajax.delete(`/api/crm/leads/${id}`)
            .then(() => {
                UI.createAlert('Lead deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to delete lead', 'danger');
            });
    }
}
</script>
<?php $this->endSection() ?>
