<?php $this->layout('layouts/app', ['title' => 'Edit Attribute']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Attribute</h1>
        <a href="/pim/attributes" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="attributeForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Attribute Code *</label>
                                <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($attribute['code']) ?>" readonly>
                                <small class="text-muted">Cannot be changed</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Label *</label>
                                <input type="text" name="label" class="form-control" value="<?= htmlspecialchars($attribute['label']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Attribute Type *</label>
                            <select name="type" id="attributeType" class="form-select" disabled>
                                <option value="text" <?= $attribute['type'] === 'text' ? 'selected' : '' ?>>Text</option>
                                <option value="textarea" <?= $attribute['type'] === 'textarea' ? 'selected' : '' ?>>Text Area</option>
                                <option value="number" <?= $attribute['type'] === 'number' ? 'selected' : '' ?>>Number</option>
                                <option value="decimal" <?= $attribute['type'] === 'decimal' ? 'selected' : '' ?>>Decimal</option>
                                <option value="date" <?= $attribute['type'] === 'date' ? 'selected' : '' ?>>Date</option>
                                <option value="boolean" <?= $attribute['type'] === 'boolean' ? 'selected' : '' ?>>Boolean</option>
                                <option value="select" <?= $attribute['type'] === 'select' ? 'selected' : '' ?>>Select</option>
                                <option value="multiselect" <?= $attribute['type'] === 'multiselect' ? 'selected' : '' ?>>Multi-select</option>
                            </select>
                            <small class="text-muted">Type cannot be changed (data integrity)</small>
                        </div>
                        
                        <?php if (in_array($attribute['type'], ['select', 'multiselect'])): ?>
                        <div id="optionsSection">
                            <div class="mb-3">
                                <label class="form-label">Options</label>
                                <div id="optionsList">
                                    <?php 
                                    $options = json_decode($attribute['options'] ?? '[]', true);
                                    foreach ($options as $option): 
                                    ?>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control option-input" value="<?= htmlspecialchars($option) ?>">
                                        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Default Value</label>
                            <input type="text" name="default_value" class="form-control" value="<?= htmlspecialchars($attribute['default_value'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Validation Rules</label>
                            <input type="text" name="validation_rules" class="form-control" value="<?= htmlspecialchars($attribute['validation_rules'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="<?= $attribute['sort_order'] ?? 0 ?>" min="0">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_required" class="form-check-input" id="isRequired" value="1" <?= $attribute['is_required'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isRequired">Required</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_searchable" class="form-check-input" id="isSearchable" value="1" <?= $attribute['is_searchable'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isSearchable">Searchable</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_filterable" class="form-check-input" id="isFilterable" value="1" <?= $attribute['is_filterable'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isFilterable">Filterable</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/attributes" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Attribute</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function addOption() {
    const optionsList = document.getElementById('optionsList');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" class="form-control option-input" placeholder="Option value">
        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i>
        </button>
    `;
    optionsList.appendChild(div);
}

document.getElementById('attributeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Collect options
    const optionsInputs = document.querySelectorAll('.option-input');
    if (optionsInputs.length > 0) {
        const options = Array.from(optionsInputs)
            .map(input => input.value.trim())
            .filter(v => v);
        data.options = JSON.stringify(options);
    }
    
    data.is_required = formData.has('is_required') ? 1 : 0;
    data.is_searchable = formData.has('is_searchable') ? 1 : 0;
    data.is_filterable = formData.has('is_filterable') ? 1 : 0;
    
    try {
        await Ajax.put('/api/pim/attributes/<?= $attribute['id'] ?>', data);
        UI.createAlert('Attribute updated successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/attributes';
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to update attribute', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
