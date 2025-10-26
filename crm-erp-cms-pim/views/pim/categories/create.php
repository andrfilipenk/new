<?php $this->layout('layouts/app', ['title' => 'Create Category']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Category</h1>
        <a href="/pim/categories" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="categoryForm">
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" required>
                            <small class="text-muted">URL-friendly name (auto-generated from category name)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Category</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (Root Category)</option>
                                <?php if (!empty($categories)): ?>
                                    <?php 
                                    function renderCategoryOptions($categories, $parentId = null, $level = 0) {
                                        $result = '';
                                        foreach ($categories as $category) {
                                            if ($category['parent_id'] == $parentId) {
                                                $indent = str_repeat('&nbsp;&nbsp;', $level);
                                                $result .= '<option value="' . $category['id'] . '">' . $indent . htmlspecialchars($category['name']) . '</option>';
                                                $result .= renderCategoryOptions($categories, $category['id'], $level + 1);
                                            }
                                        }
                                        return $result;
                                    }
                                    echo renderCategoryOptions($categories);
                                    ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image" class="form-control">
                            <small class="text-muted">Category thumbnail image URL</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0">
                            <small class="text-muted">Lower numbers appear first</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Meta Title (SEO)</label>
                            <input type="text" name="meta_title" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Meta Description (SEO)</label>
                            <textarea name="meta_description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                            <label class="form-check-label" for="isActive">Active Category</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/categories" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <strong>Category Hierarchy:</strong> You can create nested categories by selecting a parent category.
                    </p>
                    <p class="text-muted">
                        <strong>Slug:</strong> Will be auto-generated from the category name if left empty.
                    </p>
                    <p class="text-muted">
                        <strong>SEO:</strong> Meta title and description help with search engine optimization.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('input', (e) => {
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
    document.querySelector('input[name="slug"]').value = slug;
});

document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.is_active = formData.has('is_active') ? 1 : 0;
    data.parent_id = data.parent_id || null;
    
    try {
        const response = await Ajax.post('/api/pim/categories', data);
        UI.createAlert('Category created successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/categories/' + response.category.id;
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to create category', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
