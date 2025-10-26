# Visual Workflow Designer Documentation

## Overview

The Visual Workflow Designer is an intuitive, drag-and-drop interface for creating and managing workflows without writing code. It provides a canvas-based approach to designing multi-step approval processes, notifications, and automation workflows.

## Features

### ✨ Key Capabilities

- **Drag-and-Drop Interface**: Easily add workflow steps by dragging from the palette onto the canvas
- **Visual Canvas**: Grid-based design area with panning and zooming
- **Real-Time Validation**: Instant feedback on workflow configuration issues
- **Step Types**:
  - Approval Steps (role-based approval)
  - Notification Steps (send notifications)
  - Automation Steps (execute automated actions)
  - Conditional Steps (branching logic)
  - Parallel Steps (concurrent approvals)
- **Auto-Layout**: Automatically arrange steps in a clean layout
- **Context Menu**: Right-click for quick actions (edit, duplicate, delete, reorder)
- **Workflow Management**: Save, load, and manage multiple workflows
- **Export/Import**: Share workflow configurations

## Getting Started

### Accessing the Designer

Navigate to `/workflow-designer` in your browser to access the visual designer interface.

### Interface Layout

The designer is divided into three main areas:

1. **Left Sidebar** - Workflow Properties & Step Palette
2. **Center Canvas** - Visual workflow design area
3. **Right Sidebar** - Step Properties & Preview

## Creating a Workflow

### Step 1: Set Workflow Properties

In the left sidebar, configure:
- **Name**: Descriptive name for the workflow
- **Entity Type**: The entity this workflow applies to (e.g., PurchaseOrder, Leave, Expense)
- **Description**: Optional description of the workflow's purpose
- **Active**: Toggle whether the workflow is active

### Step 2: Add Steps

1. Locate the step type in the **Step Types Palette**
2. **Drag** the step type onto the canvas
3. **Drop** it where you want it in the workflow sequence

Available step types:

#### 🟢 Approval Step
Requires manual approval from a specific role.

**Configuration:**
- Step Name
- Required Role (who can approve)
- Auto-approve option

**Example Use:** "Manager Approval", "Finance Review"

#### 🔵 Notification Step
Sends notifications to users or roles.

**Configuration:**
- Step Name
- Target Role (optional)
- Notification Template

**Example Use:** "Notify Requester", "Alert Procurement Team"

#### 🟡 Automation Step
Executes automated actions or integrations.

**Configuration:**
- Step Name
- Action Configuration (JSON)

**Example Use:** "Update Inventory", "Send to ERP System"

#### 🟣 Conditional Step
Branch workflow based on conditions.

**Configuration:**
- Step Name
- Condition Configuration (JSON)

**Example Use:** "Check Amount", "Verify Stock Level"

#### 🔷 Parallel Step
Execute multiple approvals concurrently.

**Configuration:**
- Step Name
- Multiple Required Roles

**Example Use:** "Department Heads Approval", "Multi-Reviewer"

### Step 3: Configure Step Properties

1. **Click** on a step in the canvas
2. The right sidebar shows the **Step Properties** panel
3. Fill in the required fields
4. Click **Update Step** to save changes

### Step 4: Arrange Steps

**Automatic Layout:**
- Click **Auto Layout** button to automatically arrange steps

**Manual Arrangement:**
- **Drag** steps on the canvas to reposition
- Steps connect automatically based on sequence order

**Reorder via Context Menu:**
- **Right-click** on a step
- Select **Move Up** or **Move Down**

### Step 5: Validate Workflow

Click the **Validate** button to check:
- Workflow has a name
- Entity type is specified
- All steps have required configuration
- Approval steps have assigned roles

### Step 6: Save Workflow

Click **Save Workflow** to persist your design.

## Managing Workflows

### Loading Existing Workflows

1. Click **Load Workflow**
2. Select from the list of saved workflows
3. Click **Load** to open it in the designer

### Editing Workflows

1. Load the workflow
2. Make desired changes
3. Save the workflow (overwrites existing)

### Deleting Workflows

**From Load Dialog:**
- Click the delete button next to a workflow

**From Designer:**
- Load the workflow
- Use **Clear Canvas** to start fresh

## Step Operations

### Context Menu Actions

**Right-click** on any step to access:

- **Edit Step**: Select and show properties
- **Duplicate**: Create a copy of the step
- **Delete**: Remove the step
- **Move Up**: Move earlier in sequence
- **Move Down**: Move later in sequence

### Keyboard Shortcuts

- **Delete**: Remove selected step
- **Ctrl+D**: Duplicate selected step
- **Ctrl+S**: Save workflow

## Canvas Controls

### Zoom Controls

- **Zoom In** (+): Enlarge canvas view
- **Zoom Out** (-): Reduce canvas view
- **Fit to Screen**: Auto-fit all steps in view

### Panning

- **Click and drag** on empty canvas area to pan
- Use scrollbars for precise positioning

## API Endpoints

The designer uses the following API endpoints:

### GET `/api/workflow-designer/step-types`
Returns available step types and their configurations.

### GET `/api/workflow-designer/list`
Returns list of all saved workflows.

### GET `/api/workflow-designer/get/{id}`
Returns workflow definition with all steps.

### POST `/api/workflow-designer/save`
Saves or updates a workflow.

**Request Body:**
```json
{
  "id": null,
  "name": "Purchase Order Approval",
  "entity_type": "PurchaseOrder",
  "description": "Multi-level approval workflow",
  "is_active": true,
  "steps": [
    {
      "step_name": "Manager Approval",
      "step_type": "approval",
      "step_order": 1,
      "required_role_id": 5,
      "auto_approve": false
    }
  ]
}
```

### POST `/api/workflow-designer/validate`
Validates workflow configuration.

### DELETE `/api/workflow-designer/delete/{id}`
Deletes a workflow (if no active instances).

## Example Workflows

### Example 1: Purchase Order Approval

**Entity Type:** PurchaseOrder

**Steps:**
1. **Manager Approval** (Approval)
   - Role: Manager
   - Auto-approve: No

2. **Finance Review** (Approval)
   - Role: Finance
   - Auto-approve: No

3. **Director Approval** (Approval)
   - Role: Director
   - Auto-approve: No

4. **Notify Procurement** (Notification)
   - Role: Procurement
   - Template: "PO #{id} approved"

### Example 2: Leave Request

**Entity Type:** Leave

**Steps:**
1. **Supervisor Approval** (Approval)
   - Role: Supervisor
   - Auto-approve: No

2. **Check Days** (Conditional)
   - Condition: days > 5
   - Branch to HR if true

3. **HR Approval** (Approval)
   - Role: HR Manager
   - Auto-approve: No

4. **Update Calendar** (Automation)
   - Action: update_calendar
   - Config: {"sync": true}

### Example 3: Expense Report

**Entity Type:** Expense

**Steps:**
1. **Amount Check** (Conditional)
   - Condition: amount > 1000
   - Branch to Finance if true

2. **Manager Approval** (Approval)
   - Role: Manager
   - Auto-approve: amount < 500

3. **Finance Approval** (Approval)
   - Role: Finance
   - Auto-approve: No

4. **Process Payment** (Automation)
   - Action: create_payment
   - Config: {"method": "bank_transfer"}

## Best Practices

### Workflow Design

1. **Keep It Simple**: Start with basic workflows and add complexity as needed
2. **Clear Names**: Use descriptive step names that indicate the action
3. **Logical Flow**: Arrange steps in the order they should execute
4. **Test First**: Create test workflows before deploying to production
5. **Document**: Use the description field to explain workflow purpose

### Step Configuration

1. **Required Fields**: Always fill in required fields before saving
2. **Role Assignment**: Ensure roles exist before assigning to approval steps
3. **Notification Templates**: Use placeholders like {id}, {amount} for dynamic content
4. **JSON Validation**: Validate JSON in automation/conditional steps

### Performance

1. **Minimize Steps**: Use only necessary steps to keep workflows fast
2. **Parallel Processing**: Use parallel steps for concurrent approvals
3. **Auto-Approve**: Use auto-approve for low-value transactions
4. **Avoid Loops**: Don't create circular dependencies

## Troubleshooting

### Steps Not Connecting

**Issue**: Steps appear disconnected
**Solution**: Steps connect based on order. Use Auto Layout or manually reorder steps.

### Validation Errors

**Issue**: Cannot save workflow
**Solution**: Click Validate to see specific errors, fix each one, then save.

### Role Not Available

**Issue**: Required role not in dropdown
**Solution**: Role must be created in the system first. Check role management.

### Workflow Not Loading

**Issue**: Workflow list is empty
**Solution**: Ensure workflows are saved to database. Check database connection.

### Canvas Not Responsive

**Issue**: Cannot drag or interact with canvas
**Solution**: Refresh the page. Check browser console for JavaScript errors.

## Technical Details

### Technology Stack

- **Frontend**: Vanilla JavaScript (ES6+)
- **UI Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Canvas**: HTML5 Canvas with SVG for connections
- **Backend**: PHP with RESTful API

### Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

### File Structure

```
/app/Controllers/WorkflowDesignerController.php  # Backend controller
/views/workflow/designer.php                     # HTML view
/public/assets/js/workflow-designer.js          # JavaScript logic
/public/assets/css/workflow-designer.css        # Styling
```

### Database Tables

The designer uses existing workflow engine tables:
- `workflows` - Workflow definitions
- `workflow_steps` - Individual steps
- `workflow_instances` - Running instances
- `workflow_step_instances` - Step execution
- `workflow_approvals` - Approval history

## Advanced Features

### Custom Step Types

To add custom step types:

1. Modify `getStepTypes()` in `WorkflowDesignerController`
2. Add step type configuration with icon, color, and fields
3. Implement backend logic in `WorkflowEngine` to handle new type

### Integration with Other Systems

Automation steps can integrate with:
- External APIs
- Email systems
- Database updates
- File generation
- Third-party services

Configure via JSON in action_config field.

### Workflow Templates

Save common workflows as templates:
1. Create workflow
2. Export configuration
3. Share JSON with team
4. Import to create new instances

## Support

For issues or questions:
- Check this documentation
- Review example workflows
- Check browser console for errors
- Contact system administrator

## Changelog

### Version 1.0.0
- Initial release
- Drag-and-drop interface
- 5 step types
- Validation
- Save/Load workflows
- Auto-layout
- Context menu

---

**Happy Workflow Designing! 🎉**
