<?php $this->layout('layouts/app', ['title' => 'Edit Page']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Page</h1>
        <a href="/cms/pages" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <form id="pageForm">
        <div class="row">
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($page['title']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug *</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?= htmlspecialchars($page['slug']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="15"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?= htmlspecialchars($page['excerpt'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= htmlspecialchars($page['meta_keywords'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Publish</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="published_at" class="form-label">Publish Date</label>
                            <input type="datetime-local" class="form-control" id="published_at" name="published_at" 
                                   value="<?= $page['published_at'] ? date('Y-m-d\TH:i', strtotime($page['published_at'])) : '' ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Page</button>
                            <a href="/cms/pages" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Page Attributes</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Page</label>
                            <select class="form-select" id="parent_id" name="parent_id">
                                <option value="">None</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="template" class="form-label">Template</label>
                            <input type="text" class="form-control" id="template" name="template" value="<?= htmlspecialchars($page['template'] ?? 'default') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="order" class="form-label">Order</label>
                            <input type="number" class="form-control" id="order" name="order" value="<?= $page['order'] ?? 0 ?>">
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Info</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>Created:</strong><br>
                            <?= date('M d, Y H:i', strtotime($page['created_at'])) ?><br><br>
                            <strong>Updated:</strong><br>
                            <?= date('M d, Y H:i', strtotime($page['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('pageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        await Ajax.put('/api/cms/pages/<?= $page['id'] ?>', data);
        UI.createAlert('Page updated successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to update page', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
