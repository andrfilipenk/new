# QueryBuilder select() Method Fix

## Issue Summary
The `select()` method in `QueryBuilder.php` uses variadic parameters (`...$columns`), which expects individual column names as separate arguments, not an array. When an array was passed, it created nested arrays causing "Array to string conversion" errors.

## Root Cause
```php
// QueryBuilder.php line 50
public function select(...$columns): static
{
    $this->select = $columns;
    return $this;
}
```

When called as `select(['id', 'name'])`, the result was:
- `$this->select = [['id', 'name']]` ❌ (nested array)

When called as `select('id', 'name')`, the result is:
- `$this->select = ['id', 'name']` ✅ (flat array)

## Files Fixed

### 1. WorkflowDesignerController.php
**Lines Fixed:** 17-18, 25-26, 224

**Changes:**
```php
// Before
->select(['entity_type'])
->select(['id', 'name'])
->select(['id', 'name', 'entity_type', 'description', 'is_active', 'created_at'])

// After
->select('entity_type')
->select('id', 'name')
->select('id', 'name', 'entity_type', 'description', 'is_active', 'created_at')
```

### 2. ReportService.php
**Lines Fixed:** 14-18, 33-37, 93-97

**Changes:**
```php
// Before
->select([
    'DATE(order_date) as date',
    'COUNT(*) as order_count',
    ...
])

// After
->select(
    'DATE(order_date) as date',
    'COUNT(*) as order_count',
    ...
)
```

Applied to:
- `salesReport()` method
- `inventoryReport()` method
- `userActivityReport()` method

### 3. WorkflowEngine.php
**Lines Fixed:** 343, 360-368, 491

**Changes:**
```php
// Before
->select(['role_id'])
->select([
    'wsi.*',
    'wi.entity_type',
    ...
])
->select(['user_id'])

// After
->select('role_id')
->select(
    'wsi.*',
    'wi.entity_type',
    ...
)
->select('user_id')
```

Applied to:
- `getPendingApprovals()` method
- `notifyApprovers()` method

### 4. QueryBuilderTest.php
**Line Fixed:** 12

**Changes:**
```php
// Before
$sql = $qb->select(['id', 'name'])->toSql();

// After
$sql = $qb->select('id', 'name')->toSql();
```

## Verification
Confirmed zero remaining instances of array-based `select()` calls:
```bash
grep -r "->select\(\[" *.php
# Result: 0 matches
```

## Usage Guidelines

### ✅ Correct Usage
```php
// Single column
DB::table('users')->select('id')->get();

// Multiple columns (separate arguments)
DB::table('users')->select('id', 'name', 'email')->get();

// With expressions
DB::table('orders')->select(
    'DATE(created_at) as date',
    'COUNT(*) as count',
    'SUM(amount) as total'
)->get();
```

### ❌ Incorrect Usage
```php
// Array syntax (causes nested arrays)
DB::table('users')->select(['id', 'name'])->get();

// Array of expressions
DB::table('orders')->select([
    'DATE(created_at) as date',
    'COUNT(*) as count'
])->get();
```

## Impact
- **Error Fixed:** "Array to string conversion" in QueryBuilder::toSql() line 553
- **Affected Features:** 
  - Workflow Designer (entity types, roles, workflow list)
  - Reports (sales, inventory, user activity)
  - Workflow Engine (pending approvals, notifications)
  - Unit Tests

## Testing Recommendations
1. Test workflow designer page load
2. Verify workflow list API endpoint
3. Run report generation functions
4. Test pending approvals retrieval
5. Execute QueryBuilder unit tests

---
**Fixed:** 2025-10-23
**Files Modified:** 4
**Total Changes:** 8 instances corrected
