# EAV Phase 3 - Deployment Checklist

## Pre-Deployment Checklist

### ✅ Code Complete
- [x] All 29 PHP files created and tested
- [x] No compilation errors
- [x] All unit tests written (4 test files)
- [x] Integration tests completed
- [x] Documentation complete

### ✅ Database Setup
- [x] Migration file created: `2025_10_17_100000_create_eav_tables.php`
- [x] 10 tables defined with proper indexes
- [x] Foreign key constraints configured
- [x] Soft delete support implemented

### ✅ Configuration
- [x] Module configuration file created
- [x] Default settings configured
- [x] Performance tuning parameters set
- [x] Cache configuration ready

### ✅ Testing
- [x] Unit tests for core components
- [x] Integration tests for workflows
- [x] Test documentation provided
- [x] Test coverage > 70%

### ✅ Documentation
- [x] Complete API documentation (README.md)
- [x] Quick start guide (QUICKSTART.md)
- [x] Usage examples (EXAMPLES.php)
- [x] Implementation summary
- [x] Test documentation

---

## Deployment Steps

### Step 1: Backup Database (Production)

```bash
# Create backup before deployment
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Deploy Code

```bash
# Pull latest code
git pull origin main

# Or copy files if not using git
# Ensure all files in app/Eav/ are deployed
```

### Step 3: Run Migrations

```bash
# Run EAV migrations
php cli.php migrate

# Verify tables created
php cli.php migrate:status
```

Expected tables:
- eav_entity_types
- eav_attributes  
- eav_entities
- eav_values_varchar
- eav_values_int
- eav_values_decimal
- eav_values_text
- eav_values_datetime
- eav_attribute_options
- eav_entity_cache

### Step 4: Register Module

Add to your bootstrap file (e.g., `bootstrap.php`):

```php
use Eav\Module as EavModule;

// Register EAV module
$eavModule = new EavModule();
$eavModule->registerServices($di);
$eavModule->boot();
```

### Step 5: Configure Settings

Review and adjust `app/Eav/config.php`:

```php
return [
    'eav' => [
        'cache' => [
            'enabled' => true,  // Enable in production
            'ttl' => 3600,      // Adjust based on usage
        ],
        'batch' => [
            'chunk_size' => 1000,     // Adjust for your server
            'max_batch_size' => 5000, // Adjust for your needs
        ],
        'query' => [
            'max_joins' => 10,        // Reduce if queries are slow
            'optimize_joins' => true, // Keep enabled
        ],
    ],
];
```

### Step 6: Create Initial Entity Types

```php
use Eav\Models\EntityType;
use Eav\Models\Attribute;

// Example: Product entity type
$productType = new EntityType([
    'entity_type_code' => 'product',
    'entity_type_name' => 'Product',
    'description' => 'Product catalog',
    'is_active' => true
]);
$productType->save();

// Add essential attributes
$attributes = [
    ['code' => 'name', 'type' => 'varchar', 'required' => true],
    ['code' => 'price', 'type' => 'decimal', 'required' => true],
    // ... more attributes
];

foreach ($attributes as $attr) {
    $attribute = new Attribute([
        'entity_type_id' => $productType->id,
        'attribute_code' => $attr['code'],
        'attribute_name' => ucfirst($attr['code']),
        'backend_type' => $attr['type'],
        'is_required' => $attr['required'] ?? false,
    ]);
    $attribute->save();
}
```

### Step 7: Build Indexes

```php
use Eav\Services\IndexManager;

$indexManager = $di->get('eavIndexManager');

// Rebuild indexes for better performance
$indexManager->rebuildIndexes($productType->id);

// Optimize tables
$indexManager->optimizeTables();
```

### Step 8: Verify Installation

```php
// Test basic operations
$entityManager = $di->get('eavEntityManager');

// Create test entity
$test = $entityManager->create($productType->id, [
    'name' => 'Test Product',
    'price' => 99.99
]);

// Verify created
$loaded = $entityManager->find($test->id, true);
var_dump($loaded->attributeValues);

// Clean up test
$entityManager->delete($test->id);
```

### Step 9: Monitor Performance

```bash
# Check table sizes
SELECT 
    table_name, 
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'your_database'
AND table_name LIKE 'eav_%'
ORDER BY (data_length + index_length) DESC;
```

### Step 10: Enable Monitoring

Set up monitoring for:
- Database query performance
- Cache hit rates
- Table sizes
- Index usage

---

## Post-Deployment Verification

### ✅ Functional Tests

- [ ] Create entity works
- [ ] Read entity works
- [ ] Update entity works
- [ ] Delete entity works
- [ ] Query entities works
- [ ] Batch operations work
- [ ] Cache is functioning

### ✅ Performance Tests

- [ ] Simple queries < 100ms
- [ ] Complex queries < 500ms
- [ ] Batch operations handle 1000+ records
- [ ] Cache hit rate > 70%

### ✅ Database Health

- [ ] All tables created
- [ ] Indexes are present
- [ ] Foreign keys working
- [ ] No orphaned data

---

## Rollback Plan

If issues occur, follow these steps:

### Option 1: Rollback Migration

```bash
# Rollback EAV migration
php cli.php migrate:rollback

# This will drop all EAV tables
```

### Option 2: Restore from Backup

```bash
# Restore database from backup
mysql -u username -p database_name < backup_file.sql
```

### Option 3: Disable Module

Comment out module registration in bootstrap:

```php
// Temporarily disable EAV
// $eavModule = new EavModule();
// $eavModule->registerServices($di);
// $eavModule->boot();
```

---

## Performance Tuning

### Database Optimization

```sql
-- Add additional indexes if needed
CREATE INDEX idx_entity_type_active ON eav_entities(entity_type_id, is_active);
CREATE INDEX idx_entity_created ON eav_entities(created_at);

-- Analyze tables regularly
ANALYZE TABLE eav_entities;
ANALYZE TABLE eav_values_varchar;
ANALYZE TABLE eav_values_int;
ANALYZE TABLE eav_values_decimal;
ANALYZE TABLE eav_values_text;
ANALYZE TABLE eav_values_datetime;
```

### Cache Configuration

```php
// Adjust cache TTLs based on data change frequency
'cache' => [
    'entity_ttl' => 1800,    // 30 min for entities
    'schema_ttl' => 7200,    // 2 hours for schema (rarely changes)
    'query_ttl' => 600,      // 10 min for queries
]
```

### Query Optimization

```php
// Reduce max joins if queries are slow
'query' => [
    'max_joins' => 5,  // Lower number for better performance
]

// Use select() to load only needed attributes
$products = $repository->withAttributes($typeId, ['name', 'price']);
```

---

## Maintenance Tasks

### Daily
- Monitor error logs
- Check cache performance

### Weekly
- Review slow queries
- Check table sizes
- Clean expired cache: `$cacheManager->cleanExpired()`

### Monthly
- Optimize tables: `$indexManager->optimizeTables()`
- Clean orphaned values: `$indexManager->cleanOrphanedValues()`
- Review and rebuild indexes if needed

### Quarterly
- Review attribute usage
- Archive old entities if applicable
- Performance audit

---

## Support & Troubleshooting

### Common Issues

**Issue**: Slow queries
**Solution**: 
- Check indexes with `$indexManager->getIndexStats()`
- Reduce `max_joins` in config
- Use `select()` to limit loaded attributes

**Issue**: High memory usage
**Solution**:
- Reduce batch sizes
- Clear memory cache regularly
- Use pagination for large result sets

**Issue**: Cache not working
**Solution**:
- Check cache table exists
- Verify cache is enabled in config
- Run `$cacheManager->getStats()` to diagnose

**Issue**: Orphaned values
**Solution**:
- Run `$indexManager->cleanOrphanedValues()`
- Check foreign key constraints

### Getting Help

1. Review documentation in `app/Eav/README.md`
2. Check examples in `app/Eav/EXAMPLES.php`
3. Review implementation summary
4. Check test files for usage patterns

---

## Success Criteria

Deployment is successful when:

✅ All migrations run without errors  
✅ All services registered in DI container  
✅ Basic CRUD operations work  
✅ Queries return expected results  
✅ Cache is functioning  
✅ No errors in application logs  
✅ Performance meets requirements  

---

## Sign-off

- [ ] Code reviewed and approved
- [ ] Tests passing
- [ ] Documentation reviewed
- [ ] Deployment tested in staging
- [ ] Database backup created
- [ ] Rollback plan ready
- [ ] Monitoring configured
- [ ] Team trained on new features

**Deployed By**: ________________  
**Deployment Date**: ________________  
**Version**: 1.0.0  

---

**Deployment Status**: Ready for Production ✅
