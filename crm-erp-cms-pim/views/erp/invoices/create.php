<?php $this->layout('layouts/app', ['title' => 'Create Invoice']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Create Invoice</h1>
        <a href="/erp/invoices" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    
    <form id="invoiceForm">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Invoice Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="customer_id" class="form-label">Customer *</label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="">-- Select Customer --</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>">
                                        <?= htmlspecialchars($customer['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" id="invoice_date" name="invoice_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_terms" class="form-label">Payment Terms</label>
                            <input type="text" class="form-control" id="payment_terms" name="payment_terms" 
                                   placeholder="e.g., Net 30, Due on Receipt">
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
                        <h5 class="card-title mb-0">Invoice Items</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Description</th>
                                        <th style="width: 15%">Quantity</th>
                                        <th style="width: 15%">Unit Price</th>
                                        <th style="width: 10%">Tax %</th>
                                        <th style="width: 15%">Total</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineItemsContainer"></tbody>
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
                                <i class="bi bi-save"></i> Create Invoice
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='/erp/invoices'">
                                Cancel
                            </button>
                        </div>
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
                   placeholder="Item description" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" 
                   name="items[${itemCounter}][quantity]" 
                   value="1" min="1" step="0.01" required 
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
                   value="0" step="0.1" min="0" max="100"
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
    document.getElementById(`item-${id}`)?.remove();
    const index = lineItems.indexOf(id);
    if (index > -1) lineItems.splice(index, 1);
    calculateTotals();
}

function calculateLineTotal(id) {
    const qty = parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`).value) || 0;
    const price = parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`).value) || 0;
    const taxRate = parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`).value) || 0;
    
    const subtotal = qty * price;
    const tax = subtotal * (taxRate / 100);
    const total = subtotal + tax;
    
    document.getElementById(`line-total-${id}`).textContent = `$${total.toFixed(2)}`;
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;
    
    lineItems.forEach(id => {
        const qty = parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`)?.value) || 0;
        const price = parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`)?.value) || 0;
        const taxRate = parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`)?.value) || 0;
        
        const itemSubtotal = qty * price;
        subtotal += itemSubtotal;
        totalTax += itemSubtotal * (taxRate / 100);
    });
    
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const total = subtotal - discount + totalTax;
    
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${totalTax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
}

document.getElementById('invoiceForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    if (lineItems.length === 0) {
        UI.createAlert('Please add at least one line item', 'warning');
        return;
    }
    
    const formData = new FormData(e.target);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (!key.startsWith('items[')) {
            data[key] = value;
        }
    }
    
    const items = [];
    lineItems.forEach(id => {
        items.push({
            description: document.querySelector(`[name="items[${id}][description]"]`).value,
            quantity: parseFloat(document.querySelector(`[name="items[${id}][quantity]"]`).value),
            unit_price: parseFloat(document.querySelector(`[name="items[${id}][unit_price]"]`).value),
            tax_rate: parseFloat(document.querySelector(`[name="items[${id}][tax_rate]"]`).value) || 0
        });
    });
    
    data.items = JSON.stringify(items);
    
    try {
        const result = await Ajax.post('/api/erp/invoices', data);
        UI.createAlert('Invoice created successfully', 'success');
        setTimeout(() => window.location.href = '/erp/invoices/' + result.invoice.id, 1000);
    } catch (error) {
        UI.createAlert('Failed to create invoice', 'danger');
    }
});

addLineItem();
</script>
<?php $this->endSection() ?>
