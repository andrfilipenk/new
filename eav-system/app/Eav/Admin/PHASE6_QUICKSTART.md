# EAV Phase 6: Quick Start Guide
## Advanced API & Administration Interface

**Version:** 1.0  
**Date:** October 19, 2025

---

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Running Migrations](#running-migrations)
4. [API Authentication](#api-authentication)
5. [Basic API Usage](#basic-api-usage)
6. [Advanced Features](#advanced-features)
7. [Troubleshooting](#troubleshooting)

---

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Existing EAV Core (Phases 1-5) installed

### Step 1: Verify Directory Structure

Ensure the Phase 6 files are in place:
```
app/Eav/Admin/
├── Controller/
├── Service/
├── Middleware/
├── Models/
└── Provider/
```

### Step 2: Load Configuration

The configuration file is located at `app/Eav/Admin/config.php`. It will be automatically loaded when you register the service provider.

---

## Configuration

### Edit Main Config File

Add the Phase 6 configuration to your main `config.php`:

```php
// config.php
require_once __DIR__ . '/app/Eav/Admin/config.php';

return array_merge(
    [
        // Your existing config
    ],
    require __DIR__ . '/app/Eav/Admin/config.php'
);
```

### Key Configuration Options

```php
// Adjust in app/Eav/Admin/config.php

'eav_admin' => [
    'api' => [
        'rate_limit_per_minute' => 100,  // Adjust rate limit
        'token_expiry_days' => 30,       // API token expiry
    ],
    'versioning' => [
        'enabled' => true,                // Enable/disable versioning
        'retention_days' => 365,          // How long to keep versions
    ],
    'audit' => [
        'enabled' => true,                // Enable/disable audit logging
        'log_read_operations' => false,   // Log read operations
    ]
]
```

---

## Running Migrations

### Execute Database Migrations

```bash
cd c:\xampp\htdocs\new
php public/migrate.php
```

### Verify Tables Created

Check that these 8 tables were created:
- `eav_entity_versions`
- `eav_audit_log`
- `eav_api_tokens`
- `eav_import_jobs`
- `eav_export_jobs`
- `eav_reports`
- `eav_webhooks`
- `eav_user_permissions`

```sql
SHOW TABLES LIKE 'eav_%';
```

---

## API Authentication

### Generate an API Token

```php
use Eav\Admin\Models\ApiToken;

// Generate token for user
$result = ApiToken::generate(
    $userId,                    // User ID from users table
    'My Application Token',     // Descriptive name
    ['*'],                      // Scopes: ['*'] = all, or specific like ['entities.read']
    30                          // Expires in 30 days
);

echo "Token: " . $result['token'];  // Give this to the API client
// Token: 8f7d9c6b4e3a2f1d8c5b4a3e2f1d8c5b4a3e2f1d8c5b4a3e2f1d8c5b4a3e
```

### Using the Token

Include the token in the `Authorization` header:

```http
GET /api/v1/eav/entity-types HTTP/1.1
Host: your-domain.com
Authorization: Bearer 8f7d9c6b4e3a2f1d8c5b4a3e2f1d8c5b4a3e2f1d8c5b4a3e2f1d8c5b4a3e
```

---

## Basic API Usage

### 1. List Entity Types

**Request:**
```http
GET /api/v1/eav/entity-types?page=1&limit=25
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "entity_type_id": 1,
      "entity_type_code": "product",
      "entity_type_label": "Product",
      "entity_table": "eav_entity_product",
      "storage_strategy": "eav",
      "is_active": 1
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "limit": 25,
    "total_pages": 1
  }
}
```

### 2. Get Entity Type Details

**Request:**
```http
GET /api/v1/eav/entity-types/product
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "entity_type_id": 1,
    "entity_type_code": "product",
    "entity_type_label": "Product",
    "storage_strategy": "eav"
  }
}
```

### 3. Create Entity

**Request:**
```http
POST /api/v1/eav/entities/product
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "name": "New Product",
  "sku": "PROD-001",
  "price": 99.99,
  "description": "Product description"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "entity_type": "product",
    "attributes": {
      "name": "New Product",
      "sku": "PROD-001",
      "price": 99.99,
      "description": "Product description"
    }
  },
  "message": "Entity created successfully"
}
```

### 4. Update Entity

**Request:**
```http
PUT /api/v1/eav/entities/product/123
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "price": 89.99,
  "description": "Updated description"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "entity_type": "product",
    "attributes": {
      "name": "New Product",
      "sku": "PROD-001",
      "price": 89.99,
      "description": "Updated description"
    }
  },
  "message": "Entity updated successfully"
}
```

### 5. Advanced Search

**Request:**
```http
POST /api/v1/eav/entities/product/search
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "filters": [
    {
      "attribute": "price",
      "operator": "between",
      "value": [10, 100]
    },
    {
      "attribute": "category",
      "operator": "=",
      "value": "Electronics"
    }
  ],
  "sort": [
    {
      "attribute": "price",
      "direction": "asc"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 50
  },
  "include_attributes": ["name", "price", "sku"]
}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "entity_type": "product",
      "attributes": {
        "name": "Product Name",
        "price": 49.99,
        "sku": "PROD-001"
      },
      "created_at": "2025-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "page": 1,
    "limit": 50,
    "total_pages": 1
  }
}
```

### 6. Bulk Create Entities

**Request:**
```http
POST /api/v1/eav/entities/product/bulk
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
  "entities": [
    {
      "name": "Product 1",
      "sku": "PROD-001",
      "price": 29.99
    },
    {
      "name": "Product 2",
      "sku": "PROD-002",
      "price": 39.99
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "index": 0,
        "id": 124,
        "status": "success"
      },
      {
        "index": 1,
        "id": 125,
        "status": "success"
      }
    ],
    "summary": {
      "total": 2,
      "successful": 2,
      "failed": 0,
      "errors": []
    }
  }
}
```

---

## Advanced Features

### Entity Versioning

```php
use Eav\Admin\Service\VersioningService;

$versioningService = $container->get(VersioningService::class);

// Get all versions for an entity
$versions = $versioningService->getVersions($entityId, $entityTypeId, 20);

// Compare two versions
$diff = $versioningService->compareVersions($entityId, $entityTypeId, 5, 6);

print_r($diff['changes']);
// Output:
// [
//     {
//         "attribute": "price",
//         "old_value": 99.99,
//         "new_value": 89.99,
//         "change_type": "modified"
//     }
// ]

// Restore to previous version
$restoredEntity = $versioningService->restoreVersion(
    $entityId,
    'product',
    5,
    $currentUserId
);
```

### Audit Logging

```php
use Eav\Admin\Service\AuditLoggingService;

$auditService = $container->get(AuditLoggingService::class);

// Query audit logs
$logs = $auditService->getLogs([
    'entity_type' => 'product',
    'start_date' => '2025-10-01',
    'end_date' => '2025-10-31',
    'user_id' => 5
], 1, 50);

// Get statistics
$stats = $auditService->getStatistics([
    'start_date' => '2025-10-01',
    'end_date' => '2025-10-31'
]);

echo "Total Events: " . $stats['total_events'];
echo "Success Rate: " . $stats['success_rate'] . "%";
```

### Import/Export

```php
use Eav\Admin\Service\ImportExportService;

$importExportService = $container->get(ImportExportService::class);

// Import CSV file
$job = $importExportService->importFile(
    'product',                      // Entity type
    '/path/to/products.csv',        // File path
    $userId,                        // User ID
    [                               // Field mapping
        'Product Name' => 'name',
        'SKU' => 'sku',
        'Price' => 'price'
    ]
);

// Check import status
$status = $importExportService->getImportJobStatus($job->job_id);
echo "Processed: {$status->processed_rows}/{$status->total_rows}";
echo "Success Rate: " . $status->getSuccessRate() . "%";

// Export entities
$exportJob = $importExportService->exportEntities(
    'product',                      // Entity type
    'csv',                          // Format
    $userId,                        // User ID
    [                               // Filters
        'price' => ['operator' => '>', 'value' => 50]
    ],
    ['name', 'sku', 'price']        // Columns to export
);

// Download export file
$file = $importExportService->getExportFile($exportJob->job_id);
// Serve file to user...
```

### Reporting

```php
use Eav\Admin\Service\ReportingEngine;

$reportingEngine = $container->get(ReportingEngine::class);

// Create custom report
$report = $reportingEngine->createReport([
    'name' => 'Monthly Sales Report',
    'type' => 'analytical',
    'entity_type_id' => $productTypeId,
    'configuration' => [
        'entity_type' => 'product',
        'group_by' => ['category'],
        'aggregations' => [
            [
                'field' => 'sales_count',
                'function' => 'sum',
                'alias' => 'total_sales'
            ],
            [
                'field' => 'price',
                'function' => 'avg',
                'alias' => 'avg_price'
            ]
        ],
        'filters' => [
            'created_at' => [
                'operator' => 'between',
                'value' => ['2025-10-01', '2025-10-31']
            ]
        ]
    ]
], $userId);

// Execute report
$result = $reportingEngine->executeReport($report->report_id);

// Export report to CSV
$exportedFile = $reportingEngine->exportReport($report->report_id, 'csv');
```

---

## Troubleshooting

### Issue: "Invalid or expired token"

**Solution:**
- Verify the token is correct
- Check if token has expired
- Ensure token is included in `Authorization` header with `Bearer ` prefix

```php
// Check token validity
$apiToken = ApiToken::verify($tokenString);
if (!$apiToken) {
    echo "Token is invalid or expired";
}
```

### Issue: "Rate limit exceeded"

**Solution:**
- Wait for the retry period (check `Retry-After` header)
- Adjust rate limits in configuration if needed
- Use different API token (each user has separate limits)

```php
// Adjust in config.php
'rate_limits' => [
    'write' => ['limit' => 200, 'window' => 60],  // Increase to 200/min
]
```

### Issue: "Validation failed"

**Solution:**
- Check the error details in the response
- Ensure all required attributes are provided
- Verify data types match attribute definitions

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed for one or more fields",
    "details": [
      {
        "field": "email",
        "message": "Email is required"
      }
    ]
  }
}
```

### Issue: Import job fails

**Solution:**
- Check file format matches expected structure
- Verify field mappings are correct
- Look at `error_details` in the import job

```php
$job = ImportJob::find($jobId);
if ($job->status === 'failed') {
    print_r($job->error_details);
}
```

---

## Next Steps

1. **Explore API Endpoints**: See full API documentation in `API_REFERENCE.md`
2. **Set Up Admin UI**: Configure web-based administration interface
3. **Configure Permissions**: Set up role-based access control
4. **Enable Webhooks**: Integrate with external systems
5. **Schedule Reports**: Set up automated reporting

---

## Support & Resources

- **Full Documentation**: `/app/Eav/PHASE6_IMPLEMENTATION_PROGRESS.md`
- **API Reference**: `/app/Eav/API_REFERENCE.md`
- **Examples**: `/examples/eav_phase6_examples.php`

---

**Last Updated:** October 19, 2025
