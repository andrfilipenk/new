<?php $this->layout('layouts/app', ['title' => 'Invoice ' . $invoice['invoice_number']]) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Invoice: <?= htmlspecialchars($invoice['invoice_number']) ?></h1>
        <div>
            <?php if ($invoice['status'] === 'draft'): ?>
            <button class="btn btn-success" onclick="sendInvoice()">
                <i class="bi bi-send"></i> Send Invoice
            </button>
            <?php endif; ?>
            <?php if ($invoice['balance'] > 0 && $invoice['status'] !== 'void'): ?>
            <button class="btn btn-primary" onclick="recordPayment()">
                <i class="bi bi-cash"></i> Record Payment
            </button>
            <?php endif; ?>
            <button class="btn btn-outline-secondary" onclick="printInvoice()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="/erp/invoices" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Invoice Details</h5>
                    <span class="badge bg-<?= $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'overdue' ? 'danger' : 'info') ?> fs-6">
                        <?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong> Customer #<?= $invoice['customer_id'] ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Invoice Date:</strong> <?= date('M d, Y', strtotime($invoice['invoice_date'])) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Due Date:</strong> <?= date('M d, Y', strtotime($invoice['due_date'])) ?>
                            <?php if ($invoice['status'] === 'overdue'): ?>
                            <br><small class="text-danger"><strong>OVERDUE!</strong></small>
                            <?php endif; ?>
                        </div>
                        <?php if ($invoice['payment_terms']): ?>
                        <div class="col-md-6">
                            <strong>Payment Terms:</strong> <?= htmlspecialchars($invoice['payment_terms']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($invoice['notes']): ?>
                    <div class="row">
                        <div class="col-12">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= number_format($item['quantity'], 2) ?></td>
                                    <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                    <td>
                                        <?= $item['tax_rate'] ?>%<br>
                                        <small class="text-muted">$<?= number_format($item['tax_amount'], 2) ?></small>
                                    </td>
                                    <td><strong>$<?= number_format($item['line_total'], 2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><strong>$<?= number_format($invoice['subtotal'], 2) ?></strong></td>
                                </tr>
                                <?php if ($invoice['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end">Discount:</td>
                                    <td>-$<?= number_format($invoice['discount_amount'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                                    <td><strong>$<?= number_format($invoice['tax_amount'], 2) ?></strong></td>
                                </tr>
                                <tr class="table-active">
                                    <td colspan="4" class="text-end"><h5>Total:</h5></td>
                                    <td><h5>$<?= number_format($invoice['total_amount'], 2) ?></h5></td>
                                </tr>
                                <?php if ($invoice['paid_amount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end">Paid:</td>
                                    <td class="text-success">-$<?= number_format($invoice['paid_amount'], 2) ?></td>
                                </tr>
                                <tr class="table-warning">
                                    <td colspan="4" class="text-end"><strong>Balance Due:</strong></td>
                                    <td><strong>$<?= number_format($invoice['balance'], 2) ?></strong></td>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($payments)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td><strong>$<?= number_format($payment['amount'], 2) ?></strong></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
                                    <td><?= htmlspecialchars($payment['reference'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($payment['notes'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Financial Summary</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Total Amount:</strong><br>
                        <h4>$<?= number_format($invoice['total_amount'], 2) ?></h4>
                    </div>
                    <div class="mb-2">
                        <strong>Paid:</strong><br>
                        <h5 class="text-success">$<?= number_format($invoice['paid_amount'], 2) ?></h5>
                    </div>
                    <div>
                        <strong>Balance Due:</strong><br>
                        <h4 class="text-<?= $invoice['balance'] > 0 ? 'danger' : 'success' ?>">
                            $<?= number_format($invoice['balance'], 2) ?>
                        </h4>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Quick Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Created:</strong><br>
                        <?= date('M d, Y H:i', strtotime($invoice['created_at'])) ?>
                    </div>
                    <?php if ($invoice['updated_at']): ?>
                    <div>
                        <strong>Last Updated:</strong><br>
                        <?= date('M d, Y H:i', strtotime($invoice['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function sendInvoice() {
    if (!confirm('Send this invoice to the customer?')) return;
    
    try {
        await Ajax.post(`/api/erp/invoices/<?= $invoice['id'] ?>/send`, {});
        UI.createAlert('Invoice sent successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to send invoice', 'danger');
    }
}

async function recordPayment() {
    const amount = prompt('Enter payment amount (Balance: $<?= number_format($invoice['balance'], 2) ?>):');
    if (!amount || parseFloat(amount) <= 0) return;
    
    const method = prompt('Payment method (cash/check/credit_card/bank_transfer):') || 'cash';
    
    try {
        await Ajax.post(`/api/erp/invoices/<?= $invoice['id'] ?>/payment`, {
            amount: parseFloat(amount),
            payment_date: new Date().toISOString().split('T')[0],
            payment_method: method,
            reference: 'Payment #' + Date.now(),
            notes: 'Payment recorded'
        });
        UI.createAlert('Payment recorded successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to record payment', 'danger');
    }
}

function printInvoice() {
    window.print();
}
</script>
<?php $this->endSection() ?>
