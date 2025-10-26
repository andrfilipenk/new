<?php $this->layout('layouts/app', ['title' => 'Invoices']) ?>

<?php $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Invoices</h1>
        <a href="/erp/invoices/create" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Invoice
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Draft</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'draft')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Sent</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'sent')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Partial</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'partially_paid')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Paid</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'paid')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title">Overdue</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'overdue')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Void</h6>
                    <h3><?= count(array_filter($invoices, fn($inv) => $inv['status'] === 'void')) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search invoices..." value="<?= $_GET['search'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="sent" <?= ($status ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="partially_paid" <?= ($status ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
                        <option value="paid" <?= ($status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="overdue" <?= ($status ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        <option value="void" <?= ($status ?? '') === 'void' ? 'selected' : '' ?>>Void</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="/erp/invoices" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Invoices Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No invoices found. <a href="/erp/invoices/create">Create one now</a>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                                <td>Customer #<?= $invoice['customer_id'] ?></td>
                                <td><?= date('M d, Y', strtotime($invoice['invoice_date'])) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($invoice['due_date'])) ?>
                                    <?php if ($invoice['status'] === 'overdue'): ?>
                                    <br><small class="text-danger">Overdue!</small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($invoice['total_amount'], 2) ?></td>
                                <td>
                                    <?php if ($invoice['paid_amount'] > 0): ?>
                                    <span class="text-success">$<?= number_format($invoice['paid_amount'], 2) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($invoice['balance'] > 0): ?>
                                    <strong class="text-danger">$<?= number_format($invoice['balance'], 2) ?></strong>
                                    <?php else: ?>
                                    <span class="text-muted">$0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'sent' => 'info',
                                        'partially_paid' => 'warning',
                                        'paid' => 'success',
                                        'overdue' => 'danger',
                                        'void' => 'dark'
                                    ];
                                    $color = $statusColors[$invoice['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>">
                                        <?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/erp/invoices/<?= $invoice['id'] ?>" class="btn btn-outline-primary">View</a>
                                        <?php if ($invoice['status'] === 'draft'): ?>
                                        <button class="btn btn-outline-success" onclick="sendInvoice(<?= $invoice['id'] ?>)">Send</button>
                                        <?php endif; ?>
                                        <?php if ($invoice['balance'] > 0 && $invoice['status'] !== 'void'): ?>
                                        <button class="btn btn-outline-info" onclick="recordPayment(<?= $invoice['id'] ?>, <?= $invoice['balance'] ?>)">Pay</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
async function sendInvoice(id) {
    if (!confirm('Send this invoice to the customer?')) return;
    
    try {
        await Ajax.post(`/api/erp/invoices/${id}/send`, {});
        UI.createAlert('Invoice sent successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to send invoice', 'danger');
    }
}

async function recordPayment(id, balance) {
    const amount = prompt(`Enter payment amount (Balance: $${balance.toFixed(2)}):`);
    if (!amount || parseFloat(amount) <= 0) return;
    
    const paymentMethod = prompt('Payment method (cash/check/credit_card/bank_transfer/other):') || 'cash';
    
    try {
        await Ajax.post(`/api/erp/invoices/${id}/payment`, {
            amount: parseFloat(amount),
            payment_date: new Date().toISOString().split('T')[0],
            payment_method: paymentMethod,
            notes: 'Payment recorded'
        });
        UI.createAlert('Payment recorded successfully', 'success');
        setTimeout(() => location.reload(), 1000);
    } catch (error) {
        UI.createAlert('Failed to record payment', 'danger');
    }
}
</script>
<?php $this->endSection() ?>
