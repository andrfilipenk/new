<?php $this->layout('layouts/app', ['title' => 'Create Purchase Order']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Purchase Order</h1>
        <a href="/erp/purchases" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    
    <form id="purchaseOrderForm">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Purchase Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vendor_id" class="form-label">Vendor *</label>
                                <select class="form-select" id="vendor_id" name="vendor_id" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?= $vendor['id'] ?>">
                                        <?= htmlspecialchars($vendor['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="warehouse_id" class="form-label">Warehouse *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">-- Select Warehouse --</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?= $warehouse['id'] ?>">
                                        <?= htmlspecialchars($warehouse['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="order_date" class="form-label">Order Date *</label>
                                <input type="date" class="form-control" id="order_date" name="order_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="expected_date" class="form-label">Expected Delivery Date *</label>
                                <input type="date" class="form-control" id="expected_date" name="expected_date" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_terms" class="form-label">Payment Terms</label>
                            <input type="text" class="form-control" id="payment_terms" name="payment_terms" 
                                   placeholder="e.g., Net 30, Net 60">
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Line Items -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Order Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 30%">Product/Description</th>
                                        <th style="width: 15%">Quantity</th>
                                        <th style="width: 15%">Unit Price</th>
                                        <th style="width: 10%">Tax %</th>
                                        <th style="width: 15%">Line Total</th>
                                        <th style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="lineItemsContainer">
                                    <!-- Items will be added here -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong id="subtotal">$0.00</strong></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">
                                            <label for="discount_amount">Discount:</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="discount_amount" name="discount_amount" 
                                                   value="0" step="0.01" min="0" onchange="calculateTotals()">
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">
                                            <label for="shipping_cost">Shipping:</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="shipping_cost" name="shipping_cost" 
                                                   value="0" step="0.01" min="0" onchange="calculateTotals()">
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                        <td><strong id="tax">$0.00</strong></td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td colspan="4" class="text-end"><h5>Total:</h5></td>
                                        <td><h5 id="total">$0.00</h5></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save"></i> Save as Draft
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='/erp/purchases'">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Quick Tips</h6>
                    </div>
                    <div class="card-body">
                        <small class="text-muted">
                            <ul class="mb-0">
                                <li>Add at least one line item</li>
                                <li>Select vendor and warehouse</li>
                                <li>Set expected delivery date</li>
                                <li>Review totals before saving</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let itemCounter = 0;
const lineItems = [];

function addLineItem() {
    itemCounter++;
    const row = document.createElement('tr');
    row.id = `item-${itemCounter}`;
    row.innerHTML = `
        <td>
            <input type="text" class="form-control form-control-sm" 
                   name="items[${itemCounter}][description]" 
                   placeholder="Product description" required>
            <input type="hidden" name="items[${itemCounter}][product_id]" value="0">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" 
                   name="items[${itemCounter}][quantity]" 
                   placeholder="Qty" min="1" value="1" required 
                   onchange="calculateLineTotal(${itemCounter})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" 
                   name="items[${itemCounter}][unit_price]" 
                   placeholder="0.00" step="0.01" min="0" required 
                   onchange="calculateLineTotal(${itemCounter})">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" 
                   name="items[${itemCounter}][tax_rate]" 
                   placeholder="0" step="0.1" min="0" max="100" value="0"
                   onchange="calculateLineTotal(${itemCounter})">
        </td>
        <td>
            <strong id="line-total-${itemCounter}">$0.00</strong>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="removeLineItem(${itemCounter})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    document.getElementById('lineItemsContainer').appendChild(row);
    lineItems.push(itemCounter);
}

function removeLineItem(id) {
    const row = document.getElementById(`item-${id}`);
    if (row) {
        row.remove();
        const index = lineItems.indexOf(id);
        if (index > -1) {
            lineItems.splice(index, 1);
        }
        calculateTotals();
    }
}

function calculateLineTotal(id) {
    const quantity = parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`).value) || 0;
    const unitPrice = parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`).value) || 0;
    const taxRate = parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`).value) || 0;
    
    const subtotal = quantity * unitPrice;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    document.getElementById(`line-total-${id}`).textContent = `$${total.toFixed(2)}`;
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    
    lineItems.forEach(id => {
        const quantity = parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`)?.value) || 0;
        const unitPrice = parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`)?.value) || 0;
        const taxRate = parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`)?.value) || 0;
        
        const itemSubtotal = quantity * unitPrice;
        subtotal += itemSubtotal;
        totalTax += itemSubtotal * (taxRate / 100);
    });
    
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping_cost').value) || 0;
    const total = subtotal - discount + shipping + totalTax;
    
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${totalTax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
}

document.getElementById('purchaseOrderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (lineItems.length === 0) {
        UI.createAlert('Please add at least one line item', 'warning');
        return;
    }
    
    const formData = new FormData(e.target);
    const data = {};
    
    // Get form fields
    for (let [key, value] of formData.entries()) {
        if (!key.startsWith('items[')) {
            data[key] = value;
        }
    }
    
    // Build items array
    const items = [];
    lineItems.forEach(id => {
        items.push({
            product_id: parseInt(document.querySelector(`[name="items[${id}][product_id]"]`).value) || 0,
            description: document.querySelector(`[name="items[${id}][description]"]`).value,
            quantity: parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`).value),
            unit_price: parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`).value),
            tax_rate: parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`).value) || 0
        });
    });
    
    data.items = JSON.stringify(items);
    
    try {
        const result = await Ajax.post('/api/erp/purchase-orders', data);
        UI.createAlert('Purchase order created successfully', 'success');
        setTimeout(() => window.location.href = '/erp/purchases/' + result.purchase_order.id, 1000);
    } catch (error) {
        UI.createAlert('Failed to create purchase order: ' + (error.message || 'Unknown error'), 'danger');
    }
});

// Add initial line item
addLineItem();
</script>
<?php $this->endSection() ?>
