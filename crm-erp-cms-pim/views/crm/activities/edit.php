<?php $this->layout('layouts/app', ['title' => isset($activity) ? 'Edit Activity' : 'Create Activity']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= isset($activity) ? 'Edit Activity' : 'Create Activity' ?></h1>
        <a href="/crm/activities" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= isset($activity) ? "/crm/activities/{$activity['id']}" : '/crm/activities' ?>">
                        <?php if (isset($activity)): ?>
                        <input type="hidden" name="_method" value="PUT">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="call" <?= ($activity['type'] ?? '') === 'call' ? 'selected' : '' ?>>Call</option>
                                    <option value="meeting" <?= ($activity['type'] ?? '') === 'meeting' ? 'selected' : '' ?>>Meeting</option>
                                    <option value="email" <?= ($activity['type'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                                    <option value="task" <?= ($activity['type'] ?? '') === 'task' ? 'selected' : '' ?>>Task</option>
                                    <option value="note" <?= ($activity['type'] ?? '') === 'note' ? 'selected' : '' ?>>Note</option>
                                    <option value="other" <?= ($activity['type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low" <?= ($activity['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                                    <option value="normal" <?= ($activity['priority'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="high" <?= ($activity['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                                    <option value="urgent" <?= ($activity['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?= htmlspecialchars($activity['subject'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($activity['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="related_to_type" class="form-label">Related To Type *</label>
                                <select class="form-select" id="related_to_type" name="related_to_type" required>
                                    <option value="account" <?= ($activity['related_to_type'] ?? $relatedType ?? '') === 'account' ? 'selected' : '' ?>>Account</option>
                                    <option value="contact" <?= ($activity['related_to_type'] ?? $relatedType ?? '') === 'contact' ? 'selected' : '' ?>>Contact</option>
                                    <option value="lead" <?= ($activity['related_to_type'] ?? $relatedType ?? '') === 'lead' ? 'selected' : '' ?>>Lead</option>
                                    <option value="opportunity" <?= ($activity['related_to_type'] ?? $relatedType ?? '') === 'opportunity' ? 'selected' : '' ?>>Opportunity</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="related_to_id" class="form-label">Related To ID *</label>
                                <input type="number" class="form-control" id="related_to_id" name="related_to_id" 
                                       value="<?= htmlspecialchars($activity['related_to_id'] ?? $relatedId ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="datetime-local" class="form-control" id="due_date" name="due_date" 
                                       value="<?= isset($activity['due_date']) ? date('Y-m-d\TH:i', strtotime($activity['due_date'])) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="planned" <?= ($activity['status'] ?? 'planned') === 'planned' ? 'selected' : '' ?>>Planned</option>
                                    <option value="in_progress" <?= ($activity['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= ($activity['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= ($activity['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/crm/activities" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <?= isset($activity) ? 'Update Activity' : 'Create Activity' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
