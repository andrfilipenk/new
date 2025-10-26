<?php $this->layout('layouts/app', ['title' => 'Edit Category']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Category</h1>
        <a href="/pim/categories/<?= $category['id'] ?>" class="btn btn-secondary">
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
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($category['slug']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Parent Category</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None (Root Category)</option>
                                <?php if (!empty($categories)): ?>
                                    <?php 
                                    function renderCategoryOptions($categories, $currentId, $parentId = null, $level = 0) {
                                        $result = '';
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $currentId) continue; // Can't be own parent
                                            if ($cat['parent_id'] == $parentId) {
                                                $indent = str_repeat('&nbsp;&nbsp;', $level);
                                                $selected = ($cat['id'] == $currentId) ? 'selected' : '';
                                                $result .= '<option value="' . $cat['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($cat['name']) . '</option>';
                                                $result .= renderCategoryOptions($categories, $currentId, $cat['id'], $level + 1);
                                            }
                                        }
                                        return $result;
                                    }
                                    echo renderCategoryOptions($categories, $category['id']);
                                    ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="url" name="image" class="form-control" value="<?= htmlspecialchars($category['image'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= $category['sort_order'] ?? 0 ?>" min="0">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Meta Title (SEO)</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($category['meta_title'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Meta Description (SEO)</label>
                            <textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($category['meta_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" <?= $category['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active Category</label>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/categories/<?= $category['id'] ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    data.is_active = formData.has('is_active') ? 1 : 0;
    data.parent_id = data.parent_id || null;
    
    try {
        await Ajax.put('/api/pim/categories/<?= $category['id'] ?>', data);
        UI.createAlert('Category updated successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/categories/<?= $category['id'] ?>';
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to update category', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
