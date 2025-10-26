<?php $this->layout('layouts/app', ['title' => 'Activities']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Activities</h1>
        <a href="/crm/activities/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Activity
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="planned" <?= ($status ?? '') === 'planned' ? 'selected' : '' ?>>Planned</option>
                        <option value="in_progress" <?= ($status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= ($status ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="call">Call</option>
                        <option value="meeting">Meeting</option>
                        <option value="email">Email</option>
                        <option value="task">Task</option>
                        <option value="note">Note</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="/crm/activities" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Activities Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Related To</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activities)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No activities found</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <tr class="<?= isset($activity['due_date']) && strtotime($activity['due_date']) < time() && $activity['status'] === 'planned' ? 'table-danger' : '' ?>">
                            <td><strong><?= htmlspecialchars($activity['subject']) ?></strong></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= ucfirst($activity['type']) ?>
                                </span>
                            </td>
                            <td><?= ucfirst($activity['related_to_type']) ?> #<?= $activity['related_to_id'] ?></td>
                            <td><?= $activity['due_date'] ? date('M d, Y', strtotime($activity['due_date'])) : '-' ?></td>
                            <td>
                                <?php
                                $priorityBadges = [
                                    'low' => 'secondary',
                                    'normal' => 'info',
                                    'high' => 'warning',
                                    'urgent' => 'danger',
                                ];
                                $badge = $priorityBadges[$activity['priority']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucfirst($activity['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'planned' => 'primary',
                                    'in_progress' => 'warning',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary',
                                ];
                                $badge = $statusBadges[$activity['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= ucfirst(str_replace('_', ' ', $activity['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($activity['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($activity['status'] !== 'completed'): ?>
                                    <button class="btn btn-outline-success" onclick="markCompleted(<?= $activity['id'] ?>)">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <a href="/crm/activities/<?= $activity['id'] ?>/edit" class="btn btn-outline-secondary">Edit</a>
                                    <button class="btn btn-outline-danger" onclick="deleteActivity(<?= $activity['id'] ?>)">Delete</button>
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
function deleteActivity(id) {
    if (confirm('Are you sure you want to delete this activity?')) {
        Ajax.delete(`/api/crm/activities/${id}`)
            .then(() => {
                UI.createAlert('Activity deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            })
            .catch(error => {
                UI.createAlert('Failed to delete activity', 'danger');
            });
    }
}

function markCompleted(id) {
    Ajax.post(`/api/crm/activities/${id}/complete`)
        .then(() => {
            UI.createAlert('Activity marked as completed', 'success');
            setTimeout(() => location.reload(), 1000);
        })
        .catch(error => {
            UI.createAlert('Failed to update activity', 'danger');
        });
}
</script>
<?php $this->endSection() ?>
