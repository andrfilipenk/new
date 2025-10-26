<?php 
use Core\View\ViewEngine;
ViewEngine::extend('layouts/app'); 
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-currency-dollar"></i> Finance Dashboard</h2>
            <p class="text-muted">Financial overview and revenue tracking</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-3 p-3">
                                <i class="bi bi-cash-stack text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-3 p-3">
                                <i class="bi bi-clock-history text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Revenue</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['pending_revenue'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-gradient rounded-3 p-3">
                                <i class="bi bi-arrow-down-circle text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Expenses</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['total_expenses'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-3 p-3">
                                <i class="bi bi-graph-up text-white fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Net Profit</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['net_profit'], 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Invoice Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <h4><?php echo $stats['invoice_count']['total']; ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h4 class="text-success"><?php echo $stats['invoice_count']['paid']; ?></h4>
                            <small class="text-muted">Paid</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h4 class="text-warning"><?php echo $stats['invoice_count']['pending']; ?></h4>
                            <small class="text-muted">Pending</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <h4 class="text-danger"><?php echo $stats['invoice_count']['overdue']; ?></h4>
                            <small class="text-muted">Overdue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/erp/invoices/create" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Create Invoice
                        </a>
                        <a href="/erp/invoices" class="btn btn-outline-primary">
                            <i class="bi bi-list me-2"></i>View All Invoices
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Outstanding Invoices</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['outstanding_invoices'])): ?>
                        <p class="text-muted mb-0">No outstanding invoices</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['outstanding_invoices'] as $invoice): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($invoice->invoice_number ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($invoice->customer_name ?? 'N/A'); ?></td>
                                        <td>$<?php echo number_format($invoice->total_amount ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars($invoice->due_date ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'secondary';
                                            if ($invoice->status == 'pending') $badgeClass = 'warning';
                                            if ($invoice->status == 'overdue') $badgeClass = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($invoice->status ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
