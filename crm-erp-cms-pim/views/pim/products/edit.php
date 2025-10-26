<?php $this->layout('layouts/app', ['title' => 'Edit Product']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Product: <?= htmlspecialchars($product['name']) ?></h1>
        <a href="/pim/products/<?= $product['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
    
    <form id="productForm">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU *</label>
                                <input type="text" class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="barcode" class="form-label">Barcode/EAN</label>
                                <input type="text" class="form-control" id="barcode" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="2"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pricing & Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?= $product['price'] ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" 
                                           value="<?= $product['cost'] ?? 0 ?>" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?= $product['stock_quantity'] ?? 0 ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="reorder_level" class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="<?= $product['reorder_level'] ?? 0 ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product Specifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" class="form-control" id="weight" name="weight" value="<?= $product['weight'] ?? '' ?>" step="0.01" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="length" class="form-label">Length (cm)</label>
                                <input type="number" class="form-control" id="length" name="length" value="<?= $product['length'] ?? '' ?>" step="0.1" min="0">
                            </div>
                            <div class="col-md-4">
                                <label for="width" class="form-label">Width (cm)</label>
                                <input type="number" class="form-control" id="width" name="width" value="<?= $product['width'] ?? '' ?>" step="0.1" min="0">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" class="form-control" id="height" name="height" value="<?= $product['height'] ?? '' ?>" step="0.1" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Product Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?= $product['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" 
                                   <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">
                                Featured Product
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Product</button>
                            <a href="/pim/products/<?= $product['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Product Organization</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="brand_id" class="form-label">Brand</label>
                            <select class="form-select" id="brand_id" name="brand_id">
                                <option value="">-- Select Brand --</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Select Category --</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Comma-separated">
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
                            <?= date('M d, Y H:i', strtotime($product['created_at'])) ?><br><br>
                            <strong>Updated:</strong><br>
                            <?= date('M d, Y H:i', strtotime($product['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('productForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    data.is_featured = data.is_featured === '1' ? 1 : 0;
    
    try {
        await Ajax.put('/api/pim/products/<?= $product['id'] ?>', data);
        UI.createAlert('Product updated successfully', 'success');
        setTimeout(() => window.location.href = '/pim/products/<?= $product['id'] ?>', 1000);
    } catch (error) {
        UI.createAlert('Failed to update product', 'danger');
    }
});
</script>
<?php $this->endSection() ?>
