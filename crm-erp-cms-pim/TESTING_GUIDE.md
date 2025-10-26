# CRM-ERP-CMS-PIM Platform - Comprehensive Testing Guide
**Date**: October 23, 2025  
**Purpose**: Manual testing guide for all implemented features

---

## 🧪 Testing Prerequisites

### 1. Environment Setup
- ✅ Database migrated (run `php cli/migrate.php` if needed)
- ✅ Database seeded (run `php cli/seed.php` if needed)
- ✅ Apache/XAMPP running
- ✅ URL accessible at http://crm.local/

### 2. Test User Credentials
```
Admin User:
- Email: admin@example.com
- Password: password123
- Role: Super Admin (full access)

Manager User:
- Email: manager@example.com
- Password: password123
- Role: Manager (CRM access)

Sales User:
- Email: sales@example.com
- Password: password123
- Role: Sales Rep (CRM access)
```

---

## Phase 1: Authentication Testing ✅

### Test 1.1: Login Functionality
**URL**: http://crm.local/login

**Steps**:
1. Navigate to login page
2. Enter: admin@example.com / password123
3. Click "Sign In"

**Expected Results**:
- ✅ Redirect to /dashboard
- ✅ Flash message: "Login successful! Welcome back."
- ✅ User name displayed in header
- ✅ Session cookie created (PHPSESSID)

**Test Cases**:
- ✅ Valid credentials → Success
- ✅ Invalid email → Error message
- ✅ Invalid password → Error message
- ✅ Empty fields → Validation errors
- ✅ Remember me checkbox → Extended session
- ✅ CSRF token validation → Protected

### Test 1.2: Dashboard Display
**URL**: http://crm.local/dashboard (requires login)

**Expected Elements**:
- ✅ 8 Statistics Cards:
  1. Total Leads (count)
  2. Total Opportunities (count)
  3. Total Revenue (amount)
  4. Pending Tasks (count)
  5. Customers (count)
  6. Low Stock Items (count)
  7. Published Pages (count)
  8. Active Products (count)

- ✅ 4 Module Quick Access Cards:
  1. CRM Module → /crm/leads
  2. ERP Module → /erp/sales
  3. CMS Module → /cms/pages
  4. PIM Module → /pim/products

**Verification**:
```javascript
// Open browser console and run:
console.log('Statistics cards:', document.querySelectorAll('.card').length);
console.log('Module cards:', document.querySelectorAll('.module-card').length);
```

### Test 1.3: Logout Functionality
**Steps**:
1. Click logout button/link
2. Verify redirect to /login
3. Try accessing /dashboard (should redirect to login)

**Expected Results**:
- ✅ Session destroyed
- ✅ Flash message: "You have been logged out successfully."
- ✅ Cannot access protected pages

---

## Phase 2: CRM Module Testing (100% Complete) ✅

### Test 2.1: Lead Management

#### 2.1.1 Lead List View
**URL**: http://crm.local/crm/leads

**Features to Test**:
- ✅ Table displays all leads
- ✅ Search by name/email/company
- ✅ Filter by status (New, Contacted, Qualified, Converted)
- ✅ Filter by source (Website, Referral, Advertising, etc.)
- ✅ Pagination works
- ✅ View/Edit/Delete buttons per row

**Test Data**:
Create 5 test leads with different statuses:
```
Lead 1: New → Website
Lead 2: Contacted → Referral
Lead 3: Qualified → Advertising
Lead 4: Converted → Trade Show
Lead 5: New → Cold Call
```

#### 2.1.2 Create Lead
**URL**: http://crm.local/crm/leads/create

**Test Cases**:
1. **Valid Lead Creation**:
   - First Name: John
   - Last Name: Doe
   - Company: Acme Corp
   - Email: john.doe@acme.com
   - Phone: 555-1234
   - Source: Website
   - Status: New
   - Submit → Should redirect to lead detail page

2. **Validation Testing**:
   - Empty first name → Error
   - Invalid email format → Error
   - Missing company → Error
   - Missing source → Error

**Expected Results**:
- ✅ Form submits via AJAX
- ✅ Success message: "Lead created successfully"
- ✅ Redirect to /crm/leads/{id}

#### 2.1.3 View Lead (NEW)
**URL**: http://crm.local/crm/leads/{id}

**Features to Test**:
- ✅ Lead information displayed correctly
- ✅ Status badge shows correct color
- ✅ Quick actions available:
  - Mark as Contacted (if status = new)
  - Qualify Lead (if status = new/contacted)
  - Convert to Contact (if status = qualified)
  - Assign Lead
- ✅ Activity history displayed
- ✅ "Add Activity" button works
- ✅ Edit button navigates to edit page

**Test Workflow**:
1. View new lead
2. Click "Mark as Contacted" → Status changes
3. Refresh page → Status persists
4. Click "Qualify Lead" → Status changes to qualified
5. Click "Convert to Contact" → Redirects to new contact

#### 2.1.4 Edit Lead (NEW)
**URL**: http://crm.local/crm/leads/{id}/edit

**Features to Test**:
- ✅ Form pre-populated with current data
- ✅ All fields editable
- ✅ Status dropdown (New, Contacted, Qualified, Unqualified, Converted)
- ✅ Source dropdown (7 options)
- ✅ Lead score input (0-100)
- ✅ Assigned to dropdown (user list)
- ✅ Update button saves changes
- ✅ Cancel button returns to lead detail

**Test Case**:
1. Edit lead name
2. Change status to "Contacted"
3. Set score to 75
4. Add notes
5. Click Update
6. Verify changes on detail page

### Test 2.2: Contact Management

#### 2.2.1 Contact List
**URL**: http://crm.local/crm/contacts

**Features**:
- ✅ List all contacts
- ✅ Search functionality
- ✅ View/Edit/Delete actions
- ✅ Create new contact button

#### 2.2.2 Create Contact
**URL**: http://crm.local/crm/contacts/create

**Test Data**:
```
First Name: Jane
Last Name: Smith
Email: jane.smith@example.com
Title: Marketing Manager
Phone: 555-5678
Mobile: 555-8765
Account: Select from dropdown
LinkedIn: https://linkedin.com/in/janesmith
Address: 123 Main St, City, State 12345
Notes: Key decision maker
```

#### 2.2.3 View Contact
**URL**: http://crm.local/crm/contacts/{id}

**Verify**:
- ✅ All contact information displayed
- ✅ Linked account shown (if any)
- ✅ Activity history
- ✅ Edit button works

#### 2.2.4 Edit Contact
**URL**: http://crm.local/crm/contacts/{id}/edit

**Test**:
- ✅ Update contact information
- ✅ Change linked account
- ✅ Update address
- ✅ Save changes

### Test 2.3: Account Management

#### 2.3.1 Account List
**URL**: http://crm.local/crm/accounts

**Features**:
- ✅ Display all accounts
- ✅ Search by name
- ✅ Filter by type (Customer, Partner, Competitor)
- ✅ View/Edit/Delete actions

#### 2.3.2 Create Account
**URL**: http://crm.local/crm/accounts/create

**Test Data**:
```
Name: Tech Solutions Inc
Type: Customer
Industry: Technology
Website: https://techsolutions.com
Phone: 555-9999
Address: 456 Business Ave
Annual Revenue: 5000000
Employees: 50
```

#### 2.3.3 View Account
**URL**: http://crm.local/crm/accounts/{id}

**Verify**:
- ✅ Account details
- ✅ Related contacts list
- ✅ Account statistics

### Test 2.4: Opportunity Management

#### 2.4.1 Opportunity List
**URL**: http://crm.local/crm/opportunities

**Features**:
- ✅ List view with filtering
- ✅ Status badges
- ✅ Amount display

#### 2.4.2 Pipeline View
**URL**: http://crm.local/crm/opportunities/pipeline

**Test**:
- ✅ Kanban board display
- ✅ 6 stages (Prospecting, Qualification, Proposal, Negotiation, Closed Won, Closed Lost)
- ✅ Drag & drop functionality (if implemented)

#### 2.4.3 Create Opportunity
**URL**: http://crm.local/crm/opportunities/create

**Test Data**:
```
Name: New Software Deal
Account: Tech Solutions Inc
Amount: 50000
Close Date: [30 days from now]
Stage: Prospecting
Probability: 25
Expected Revenue: 12500
```

#### 2.4.4 Opportunity Workflow
**Test Sequence**:
1. Create opportunity (Prospecting)
2. View detail page
3. Click "Update Stage" → Qualification
4. Update probability to 50%
5. Move to Proposal stage
6. Update amount if needed
7. Mark as Won or Lost

### Test 2.5: Activity Management

#### 2.5.1 Activity List
**URL**: http://crm.local/crm/activities

**Features**:
- ✅ List all activities
- ✅ Filter by type (Task, Call, Meeting, Email)
- ✅ Filter by status (Pending, Completed)
- ✅ View upcoming activities
- ✅ View overdue activities

#### 2.5.2 Create Activity
**URL**: http://crm.local/crm/activities/create

**Test Cases**:
1. **Task**:
   - Subject: Follow up with lead
   - Type: Task
   - Due Date: Tomorrow
   - Priority: High
   - Related To: Lead (select lead)
   - Description: Call to discuss proposal

2. **Meeting**:
   - Subject: Product Demo
   - Type: Meeting
   - Due Date: Next week
   - Duration: 1 hour
   - Related To: Opportunity

3. **Call**:
   - Subject: Discovery Call
   - Type: Call
   - Due Date: Today
   - Related To: Contact

#### 2.5.3 Mark Activity Complete
**Steps**:
1. View activity detail
2. Click "Mark as Completed"
3. Verify status change
4. Check completion timestamp

---

## Phase 3: ERP Module Testing ✅

### Test 3.1: Purchase Order Management (NEW)

#### 3.1.1 Purchase Order List
**URL**: http://crm.local/erp/purchases

**Features to Test**:
- ✅ 6 status cards (Draft, Pending, Sent, Partial, Completed, Cancelled)
- ✅ Filter by status dropdown
- ✅ Search by PO number
- ✅ Table columns: PO#, Vendor, Order Date, Expected Date, Total, Status, Actions
- ✅ Action buttons context-aware (Approve for Draft, Receive for Sent)

#### 3.1.2 Create Purchase Order (NEW)
**URL**: http://crm.local/erp/purchases/create

**Test Workflow**:
1. **Basic Information**:
   - Select Vendor
   - Select Warehouse
   - Order Date: Today
   - Expected Delivery: +7 days
   - Payment Terms: Net 30

2. **Add Line Items**:
   - Click "Add Item"
   - Description: "Office Supplies - Pens"
   - Quantity: 100
   - Unit Price: 0.50
   - Tax: 10%
   - Line Total: $55.00 (auto-calculated)

   - Add second item:
   - Description: "Office Supplies - Paper"
   - Quantity: 50
   - Unit Price: 5.00
   - Tax: 10%
   - Line Total: $275.00

3. **Verify Calculations**:
   - Subtotal: $275.00
   - Discount: $10.00
   - Shipping: $15.00
   - Tax: $28.00
   - Total: $308.00

4. **Submit**:
   - Click "Save as Draft"
   - Verify redirect to PO detail page
   - Check PO number generated

**Validation Tests**:
- ✅ Vendor required
- ✅ Warehouse required
- ✅ At least one line item required
- ✅ Quantity must be positive
- ✅ Unit price must be positive

#### 3.1.3 View Purchase Order (NEW)
**URL**: http://crm.local/erp/purchases/{id}

**Features**:
- ✅ PO header with status badge
- ✅ Vendor and warehouse information
- ✅ Order and expected dates
- ✅ Payment terms
- ✅ Line items table
- ✅ Totals breakdown
- ✅ Action buttons (Approve, Reject, Cancel, Print)

**Test Approval Workflow**:
1. View draft PO
2. Click "Approve & Send"
3. Confirm action
4. Status changes to "Sent"
5. Approve button disappears
6. Receive button appears

**Test Receive Workflow**:
1. View sent PO
2. Click "Receive Items"
3. Enter received quantity for each item
4. Submit
5. Status changes to "Partial" or "Completed"
6. Received quantities updated

#### 3.1.4 Purchase Order Actions
**Test Cases**:
1. **Approve**: Draft → Sent
2. **Reject**: Draft → Rejected (requires reason)
3. **Receive**: Sent → Partial/Completed
4. **Cancel**: Any status → Cancelled (requires reason)
5. **Print**: Opens print dialog

### Test 3.2: Invoice Management (NEW)

#### 3.2.1 Invoice List
**URL**: http://crm.local/erp/invoices

**Features**:
- ✅ 6 status cards (Draft, Sent, Partially Paid, Paid, Overdue, Void)
- ✅ Filter by status
- ✅ Search functionality
- ✅ Columns: Invoice#, Customer, Date, Due Date, Total, Paid, Balance, Status
- ✅ Action buttons (View, Send, Pay)
- ✅ Overdue indicators

#### 3.2.2 Create Invoice (NEW)
**URL**: http://crm.local/erp/invoices/create

**Test Workflow**:
1. **Basic Information**:
   - Select Customer
   - Invoice Date: Today
   - Due Date: +30 days
   - Payment Terms: Net 30

2. **Add Line Items**:
   - Description: "Web Development Services"
   - Quantity: 40 (hours)
   - Unit Price: 100.00
   - Tax: 8%
   - Line Total: $4,320.00

   - Description: "Hosting Services"
   - Quantity: 1
   - Unit Price: 500.00
   - Tax: 8%
   - Line Total: $540.00

3. **Calculate Totals**:
   - Subtotal: $4,500.00
   - Discount: $100.00
   - Tax: $352.00
   - Total: $4,752.00

4. **Submit**:
   - Click "Create Invoice"
   - Verify invoice number generated
   - Balance = Total (no payments yet)

#### 3.2.3 View Invoice (NEW)
**URL**: http://crm.local/erp/invoices/{id}

**Features**:
- ✅ Invoice header with status
- ✅ Customer information
- ✅ Invoice and due dates
- ✅ Line items table
- ✅ Totals breakdown
- ✅ Payment history table (if any)
- ✅ Financial summary sidebar
- ✅ Actions: Send, Record Payment, Print

**Test Payment Workflow**:
1. View draft invoice
2. Click "Send Invoice"
3. Status → Sent
4. Click "Record Payment"
5. Enter amount: $2,000.00
6. Payment method: Credit Card
7. Payment date: Today
8. Submit
9. Verify:
   - Paid amount: $2,000.00
   - Balance: $2,752.00
   - Status: Partially Paid
10. Record second payment: $2,752.00
11. Status → Paid
12. Balance → $0.00

#### 3.2.4 Invoice Actions
**Test Cases**:
1. **Send**: Draft → Sent
2. **Record Payment**: Updates paid amount, balance, status
3. **Void**: Any status → Void
4. **Print**: Print-friendly view

### Test 3.3: Sales Orders
**URL**: http://crm.local/erp/sales

**Features**:
- ✅ List all sales orders
- ✅ Status statistics cards
- ✅ Confirm/Fulfill actions
- ✅ View order details

### Test 3.4: Finance Dashboard
**URL**: http://crm.local/erp/finance

**Features**:
- ✅ Revenue metrics
- ✅ Expense metrics
- ✅ Profit calculations
- ✅ Charts and visualizations

---

## Phase 4: CMS Module Testing ✅

### Test 4.1: Page Management

#### 4.1.1 Page List
**URL**: http://crm.local/cms/pages

**Features**:
- ✅ Display all pages
- ✅ Status badges (Draft, Published)
- ✅ Publish action for drafts
- ✅ Edit/View links

#### 4.1.2 Create Page (NEW)
**URL**: http://crm.local/cms/pages/create

**Test Workflow**:
1. **Basic Information**:
   - Title: "About Us"
   - Slug: "about-us" (auto-generated)
   - Content: [Rich text content]
   - Excerpt: "Learn more about our company"

2. **SEO Settings**:
   - Meta Title: "About Us - Company Name"
   - Meta Description: "Discover our mission, vision, and values"
   - Meta Keywords: "about, company, mission, values"

3. **Page Attributes**:
   - Parent Page: None
   - Template: default
   - Order: 0

4. **Publishing**:
   - Status: Published
   - Publish Date: Now

5. **Submit**:
   - Click "Create Page"
   - Verify redirect to edit page

**Validation Tests**:
- ✅ Title required
- ✅ Slug required and unique
- ✅ Slug auto-generated from title
- ✅ Special characters converted to hyphens

#### 4.1.3 Edit Page (NEW)
**URL**: http://crm.local/cms/pages/{id}/edit

**Features**:
- ✅ Form pre-populated with page data
- ✅ All fields editable
- ✅ SEO section
- ✅ Page attributes
- ✅ Info sidebar (created/updated dates)
- ✅ Update button saves changes

**Test Case**:
1. Edit page title
2. Update content
3. Change SEO meta tags
4. Update publish date
5. Click Update
6. Verify changes saved

### Test 4.2: Page Publishing Workflow
**Test Sequence**:
1. Create page as Draft
2. Review content
3. Add SEO meta tags
4. Change status to Published
5. Set publish date
6. Update page
7. Verify page appears in frontend

---

## Phase 5: PIM Module Testing ✅

### Test 5.1: Product Management

#### 5.1.1 Product List
**URL**: http://crm.local/pim/products

**Features**:
- ✅ Grid view of products
- ✅ Product cards with image placeholder
- ✅ SKU, name, price, stock
- ✅ Status badges
- ✅ Search and filters
- ✅ View/Edit actions

#### 5.1.2 Create Product (NEW)
**URL**: http://crm.local/pim/products/create

**Test Workflow**:
1. **Basic Information**:
   - Product Name: "Wireless Mouse"
   - SKU: "WM-001"
   - Barcode: "1234567890123"
   - Description: "Ergonomic wireless mouse with 2.4GHz connectivity"
   - Short Description: "Wireless mouse - Black"

2. **Pricing & Inventory**:
   - Price: $29.99
   - Cost: $15.00
   - Stock Quantity: 100
   - Reorder Level: 20

3. **Specifications**:
   - Weight: 0.15 kg
   - Length: 12 cm
   - Width: 7 cm
   - Height: 4 cm

4. **Organization**:
   - Brand: [Select from dropdown]
   - Category: [Select from dropdown]
   - Tags: wireless, mouse, computer accessories

5. **Status**:
   - Status: Active
   - Featured: Yes

6. **Submit**:
   - Click "Create Product"
   - Verify redirect to product detail

**Validation Tests**:
- ✅ Name required
- ✅ SKU required and unique
- ✅ Price must be positive
- ✅ Stock quantity must be non-negative

#### 5.1.3 Edit Product (NEW)
**URL**: http://crm.local/pim/products/{id}/edit

**Features**:
- ✅ Form pre-populated
- ✅ All fields editable
- ✅ Price/cost update
- ✅ Stock quantity management
- ✅ Specifications update
- ✅ Status toggle
- ✅ Info sidebar (created/updated dates)

**Test Case**:
1. Update product name
2. Change price from $29.99 to $24.99
3. Update stock quantity to 150
4. Add product tags
5. Mark as featured
6. Click Update
7. Verify changes saved

### Test 5.2: Product Lifecycle Test
**Complete Workflow**:
1. Create product as Draft
2. Add all information
3. Set pricing
4. Add stock quantity
5. Change status to Active
6. Mark as Featured
7. Verify product appears in catalog
8. Update price
9. Adjust stock
10. Change to Inactive

---

## Phase 6: Workflow Designer Testing ✅

### Test 6.1: Workflow Designer UI
**URL**: http://crm.local/workflow-designer

**Features to Test**:
- ✅ Designer interface loads
- ✅ Step types available (Start, Approval, Condition, Action, Notification, Delay, End)
- ✅ Create new workflow
- ✅ Add steps
- ✅ Connect steps with transitions
- ✅ Save workflow
- ✅ Validate workflow structure
- ✅ Load existing workflow
- ✅ Delete workflow

**Test Workflow Creation**:
1. Click "New Workflow"
2. Name: "Lead Approval Process"
3. Add Start step
4. Add Approval step (configuration dialog)
5. Add Condition step (check approval result)
6. Add Action step (send notification)
7. Add End step
8. Connect steps with arrows
9. Click "Save"
10. Click "Validate"
11. Verify no errors

---

## Testing Checklist Summary

### ✅ Authentication (100%)
- [x] Login with valid credentials
- [x] Login with invalid credentials
- [x] Remember me functionality
- [x] Logout
- [x] Dashboard display
- [x] Session persistence

### ✅ CRM Module (100%)
- [x] Lead list, create, **view**, **edit**, delete
- [x] Lead status workflow (new → contacted → qualified → converted)
- [x] Contact list, create, view, edit, delete
- [x] Account list, create, view, edit, delete
- [x] Opportunity list, pipeline, create, view, edit
- [x] Opportunity stage updates
- [x] Activity list, create, view, mark complete

### ✅ ERP Module (Core Features)
- [x] **Purchase order list, create, view**
- [x] **Purchase order approval workflow**
- [x] **Purchase order receiving**
- [x] **Invoice list, create, view**
- [x] **Invoice payment recording**
- [x] **Invoice status tracking**
- [x] Sales order list
- [x] Finance dashboard

### ✅ CMS Module (Page Management)
- [x] Page list
- [x] **Page create with SEO**
- [x] **Page edit**
- [x] Page publishing workflow

### ✅ PIM Module (Product Management)
- [x] Product list
- [x] **Product create with specifications**
- [x] **Product edit**
- [x] Product status management

### ✅ Workflow System
- [x] Workflow designer UI
- [x] Workflow creation
- [x] Workflow validation

---

## 🐛 Bug Reporting Template

If you encounter issues during testing, use this template:

```markdown
**Bug Title**: Brief description

**Severity**: Critical / High / Medium / Low

**Module**: CRM / ERP / CMS / PIM / Auth / Other

**URL**: http://crm.local/path/to/page

**Steps to Reproduce**:
1. Step 1
2. Step 2
3. Step 3

**Expected Result**:
What should happen

**Actual Result**:
What actually happened

**Browser**: Chrome / Firefox / Safari / Edge

**Screenshots**: [Attach if applicable]

**Console Errors**: [Paste any JavaScript errors]

**Server Errors**: [Paste any PHP errors from logs]
```

---

## 📊 Test Coverage Report Template

After completing tests, fill out this report:

```markdown
## Test Coverage Report
**Date**: [Date]
**Tester**: [Name]
**Environment**: http://crm.local/

### Summary
- Total Tests: X
- Passed: X
- Failed: X
- Blocked: X
- Pass Rate: X%

### Module Results
| Module | Tests | Passed | Failed | Notes |
|--------|-------|--------|--------|-------|
| Auth   | X     | X      | X      |       |
| CRM    | X     | X      | X      |       |
| ERP    | X     | X      | X      |       |
| CMS    | X     | X      | X      |       |
| PIM    | X     | X      | X      |       |

### Critical Issues
1. [Issue description]
2. [Issue description]

### Recommendations
1. [Recommendation]
2. [Recommendation]
```

---

**Testing Status**: Ready for Execution  
**Platform Version**: 0.1.0-alpha  
**Last Updated**: October 23, 2025

All implemented features are ready for comprehensive manual testing following this guide.
