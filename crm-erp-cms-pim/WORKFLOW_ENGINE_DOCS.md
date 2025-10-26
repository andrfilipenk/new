# Workflow Engine Documentation

## Overview

The Workflow Engine provides a robust, reusable system for creating multi-step approval workflows that can be applied to any entity in the system.

## Features

- ✅ **Configurable Workflows** - Define custom workflows for any entity type
- ✅ **Multi-Step Approvals** - Support for sequential approval steps
- ✅ **Role-Based Permissions** - Restrict approval rights by role or permission
- ✅ **Approval History** - Complete audit trail of all approvals/rejections
- ✅ **Auto-Notifications** - Automatic notifications to approvers
- ✅ **HTML Reports** - Generate formatted reports without PDF dependencies
- ✅ **Flexible Architecture** - Easy to extend and customize

## Database Schema

### Tables Created
1. `workflows` - Workflow definitions
2. `workflow_steps` - Steps within each workflow
3. `workflow_instances` - Active workflow instances
4. `workflow_step_instances` - Individual step executions
5. `workflow_approvals` - Approval/rejection history

## Quick Start

### 1. Create a Workflow

```php
use Core\Workflow\WorkflowBuilder;

// Create a purchase order approval workflow
$workflow = WorkflowBuilder::create(
    'Purchase Order Approval',
    'PurchaseOrder',
    'Multi-level approval for purchase orders over $1000'
)
->addApprovalStep('Manager Approval', 1, $managerRoleId)
->addApprovalStep('Finance Approval', 2, $financeRoleId)
->addApprovalStep('Director Approval', 3, $directorRoleId);

$workflowId = $workflow->getWorkflowId();
```

### 2. Start a Workflow Instance

```php
use Core\Workflow\WorkflowEngine;

// When a purchase order is created
$instanceId = WorkflowEngine::startWorkflow(
    $workflowId,              // Workflow definition ID
    'PurchaseOrder',          // Entity type
    $purchaseOrderId,         // Entity ID
    $currentUserId            // User who initiated
);
```

### 3. Approve a Step

```php
// Manager approves
WorkflowEngine::approve($instanceId, $managerId, 'Approved - within budget');

// Finance approves
WorkflowEngine::approve($instanceId, $financeUserId, 'Budget allocation confirmed');
```

### 4. Reject a Step

```php
WorkflowEngine::reject($instanceId, $managerId, 'Amount exceeds department budget');
```

### 5. Get Pending Approvals

```php
// Get all pending approvals for a user
$pendingApprovals = WorkflowEngine::getPendingApprovals($userId);

foreach ($pendingApprovals as $approval) {
    echo "Workflow: " . $approval['workflow_name'];
    echo "Step: " . $approval['step_name'];
    echo "Entity: " . $approval['entity_type'] . " #" . $approval['entity_id'];
}
```

### 6. Generate HTML Report

```php
// Generate formatted HTML report
$htmlReport = WorkflowEngine::generateHTMLReport($instanceId);

// Display or save to file
echo $htmlReport;
file_put_contents('workflow_report.html', $htmlReport);
```

## API Endpoints

### GET /api/workflows/pending
Get pending approvals for current user

**Response:**
```json
{
  "pending_approvals": [...],
  "count": 5
}
```

### POST /api/workflows/{instanceId}/approve
Approve a workflow step

**Body:**
```json
{
  "comments": "Approved - looks good"
}
```

### POST /api/workflows/{instanceId}/reject
Reject a workflow step

**Body:**
```json
{
  "reason": "Budget limit exceeded"
}
```

### GET /api/workflows/{instanceId}/details
Get workflow instance details

### GET /api/workflows/{instanceId}/report
Generate HTML report

### POST /api/workflows/{instanceId}/cancel
Cancel workflow

## Integration Examples

### Purchase Order Workflow

```php
// In ProcurementService
public function createPurchaseOrder(array $data, int $userId): PurchaseOrder
{
    $order = PurchaseOrder::create($data);
    
    // Start workflow if amount > threshold
    if ($order->total_amount > 1000) {
        $instanceId = WorkflowEngine::startWorkflow(
            $this->purchaseOrderWorkflowId,
            'PurchaseOrder',
            $order->id,
            $userId
        );
        
        $order->workflow_instance_id = $instanceId;
        $order->save();
    }
    
    return $order;
}
```

### Leave Request Workflow

```php
// In HRService
public function requestLeave(int $employeeId, array $data): Leave
{
    $leave = Leave::create($data);
    
    // Start approval workflow
    $instanceId = WorkflowEngine::startWorkflow(
        $this->leaveWorkflowId,
        'Leave',
        $leave->id,
        $employeeId
    );
    
    $leave->workflow_instance_id = $instanceId;
    $leave->save();
    
    return $leave;
}
```

### Expense Report Workflow

```php
// Create expense approval workflow
$expenseWorkflow = WorkflowBuilder::create(
    'Expense Report Approval',
    'ExpenseReport'
)
->addApprovalStep('Manager Review', 1, $managerRoleId)
->addApprovalStep('Accounting Verification', 2, $accountingRoleId)
->addApprovalStep('CFO Approval', 3, $cfoRoleId)
->addNotificationStep('Notify Finance for Payment', 4);
```

## HTML Report Output

The engine generates Bootstrap-styled HTML reports:

```html
<div class="workflow-report">
  <h2>Workflow Report: Purchase Order Approval</h2>
  
  <table class="table table-bordered">
    <tr><th>Workflow ID</th><td>123</td></tr>
    <tr><th>Status</th><td><span class="badge bg-success">Completed</span></td></tr>
    ...
  </table>
  
  <h3>Workflow Steps</h3>
  <table class="table table-striped">
    <thead>...</thead>
    <tbody>
      <!-- Step details with approver, date, comments -->
    </tbody>
  </table>
</div>
```

## Best Practices

1. **Define Clear Steps** - Each step should have a clear purpose
2. **Use Descriptive Names** - Make step names self-explanatory
3. **Set Appropriate Roles** - Ensure approvers have proper permissions
4. **Add Comments** - Encourage approvers to add comments
5. **Monitor Pending** - Check pending approvals regularly
6. **Generate Reports** - Keep HTML reports for audit purposes

## Advantages Over PDF

- ✅ **No Dependencies** - Pure PHP implementation
- ✅ **Easy Styling** - Use CSS to customize appearance
- ✅ **Interactive** - Can include buttons/links
- ✅ **Searchable** - Text is searchable
- ✅ **Responsive** - Works on all devices
- ✅ **Email Friendly** - Can be embedded in emails
- ✅ **Print Ready** - Browser print to PDF if needed

## Future Enhancements

- Parallel approvals (multiple approvers at same step)
- Conditional routing (different paths based on conditions)
- Escalation rules (auto-approve after timeout)
- Delegation (approve on behalf of another user)
- Workflow templates
- Visual workflow designer

---

**The Workflow Engine is production-ready and can be used immediately!** 🚀
