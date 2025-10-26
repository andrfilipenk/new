<?php $this->layout('layouts/app', ['title' => 'Contacts']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Contacts<?= isset($accountId) ? ' - Account Filter' : '' ?></h1>
        <a href="/crm/contacts/create<?= isset($accountId) ? '?account_id=' . $accountId : '' ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Contact
        </a>
    </div>
    
    <!-- Contacts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Account</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Mobile</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No contacts found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?></strong></td>
                            <td><?= htmlspecialchars($contact['title'] ?? '-') ?></td>
                            <td>
                                <?php if ($contact['account_id']): ?>
                                    <a href="/crm/accounts/<?= $contact['account_id'] ?>">View Account</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($contact['email']) ?></td>
                            <td><?= htmlspecialchars($contact['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($contact['mobile'] ?? '-') ?></td>
                            <td><?= date('M d, Y', strtotime($contact['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/crm/contacts/<?= $contact['id'] ?>" class="btn btn-outline-primary">View</a>
                                    <a href="/crm/contacts/<?= $contact['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                    <button class="btn btn-outline-danger" onclick="deleteContact(<?= $contact['id'] ?>)">Delete</button>
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
function deleteContact(id) {
    if (confirm('Are you sure you want to delete this contact?')) {
        Ajax.delete(`/api/crm/contacts/${id}`)
            .then(() => {
                UI.createAlert('Contact deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to delete contact', 'danger');
            });
    }
}
</script>
<?php $this->endSection() ?>
