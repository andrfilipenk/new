<?php $this->layout('layouts/app', ['title' => 'Create Attribute']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Attribute</h1>
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
                                <input type="text" name="code" class="form-control" required>
                                <small class="text-muted">Unique identifier (e.g., color, size, weight)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Label *</label>
                                <input type="text" name="label" class="form-control" required>
                                <small class="text-muted">Display name (e.g., Color, Size, Weight)</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Attribute Type *</label>
                            <select name="type" id="attributeType" class="form-select" required>
                                <option value="text">Text</option>
                                <option value="textarea">Text Area</option>
                                <option value="number">Number</option>
                                <option value="decimal">Decimal</option>
                                <option value="date">Date</option>
                                <option value="boolean">Boolean (Yes/No)</option>
                                <option value="select">Select (Dropdown)</option>
                                <option value="multiselect">Multi-select</option>
                            </select>
                        </div>
                        
                        <div id="optionsSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Options</label>
                                <div id="optionsList">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control option-input" placeholder="Option value">
                                        <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOption()">
                                    <i class="bi bi-plus"></i> Add Option
                                </button>
                                <small class="text-muted d-block mt-1">For select/multiselect types</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Default Value</label>
                            <input type="text" name="default_value" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Validation Rules</label>
                            <input type="text" name="validation_rules" class="form-control">
                            <small class="text-muted">E.g., min:0|max:100 or regex:/^[A-Z]/</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_required" class="form-check-input" id="isRequired" value="1">
                                    <label class="form-check-label" for="isRequired">Required</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_searchable" class="form-check-input" id="isSearchable" value="1">
                                    <label class="form-check-label" for="isSearchable">Searchable</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="is_filterable" class="form-check-input" id="isFilterable" value="1">
                                    <label class="form-check-label" for="isFilterable">Filterable</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/pim/attributes" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Attribute</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Attribute Types</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2"><strong>Text:</strong> Single line text input</p>
                    <p class="text-muted small mb-2"><strong>Text Area:</strong> Multi-line text input</p>
                    <p class="text-muted small mb-2"><strong>Number:</strong> Integer values</p>
                    <p class="text-muted small mb-2"><strong>Decimal:</strong> Decimal values (prices, weights)</p>
                    <p class="text-muted small mb-2"><strong>Date:</strong> Date picker</p>
                    <p class="text-muted small mb-2"><strong>Boolean:</strong> Yes/No checkbox</p>
                    <p class="text-muted small mb-2"><strong>Select:</strong> Dropdown with predefined options</p>
                    <p class="text-muted small mb-0"><strong>Multi-select:</strong> Multiple choice selection</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('attributeType').addEventListener('change', (e) => {
    const optionsSection = document.getElementById('optionsSection');
    if (e.target.value === 'select' || e.target.value === 'multiselect') {
        optionsSection.style.display = 'block';
    } else {
        optionsSection.style.display = 'none';
    }
});

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
    const options = Array.from(document.querySelectorAll('.option-input'))
        .map(input => input.value.trim())
        .filter(v => v);
    
    if (options.length > 0) {
        data.options = JSON.stringify(options);
    }
    
    data.is_required = formData.has('is_required') ? 1 : 0;
    data.is_searchable = formData.has('is_searchable') ? 1 : 0;
    data.is_filterable = formData.has('is_filterable') ? 1 : 0;
    
    try {
        await Ajax.post('/api/pim/attributes', data);
        UI.createAlert('Attribute created successfully', 'success');
        setTimeout(() => {
            window.location.href = '/pim/attributes';
        }, 1000);
    } catch (error) {
        UI.createAlert('Failed to create attribute', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
